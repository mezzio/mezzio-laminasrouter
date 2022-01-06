<?php

declare(strict_types=1);

namespace Mezzio\Router;

use Laminas\Http\Exception\InvalidArgumentException;
use Laminas\Psr7Bridge\Psr7ServerRequest;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouteMatch;
use Laminas\Uri\Exception\InvalidUriPartException;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;

use function array_key_exists;
use function array_merge;
use function array_reduce;
use function array_replace_recursive;
use function implode;
use function preg_match;
use function rtrim;
use function sprintf;

/**
 * Router implementation that consumes laminas-mvc TreeRouteStack.
 *
 * This router implementation consumes the TreeRouteStack from laminas-mvc (the
 * default router implementation in a Laminas application). The addRoute() method
 * injects segment routes into the TreeRouteStack. To manage 405 (Method Not
 * Allowed) errors, we inject a METHOD_NOT_ALLOWED_ROUTE route as a child
 * route, at a priority lower than method-specific routes. If the request
 * matches with this special route, we can send the HTTP allowed methods stored
 * for that path.
 */
class LaminasRouter implements RouterInterface
{
    public const METHOD_NOT_ALLOWED_ROUTE = 'method_not_allowed';

    /**
     * Store the HTTP methods allowed for each path.
     *
     * @var array
     */
    private $allowedMethodsByPath = [];

    /**
     * Map a named route to a Laminas route name to use for URI generation.
     *
     * @var array
     */
    private $routeNameMap = [];

    /** @var Route[] */
    private $routes = [];

    /**
     * Routes aggregated to inject.
     *
     * @var Route[]
     */
    private $routesToInject = [];

    /** @var TreeRouteStack */
    private $laminasRouter;

    /**
     * Lazy instantiates a TreeRouteStack if none is provided.
     */
    public function __construct(?TreeRouteStack $router = null)
    {
        if (null === $router) {
            $router = $this->createRouter();
        }

        $this->laminasRouter = $router;
    }

    public function addRoute(Route $route): void
    {
        $this->routesToInject[] = $route;
    }

    public function match(PsrRequest $request): RouteResult
    {
        // Must inject routes prior to matching.
        $this->injectRoutes();

        try {
            $laminasRequest = Psr7ServerRequest::toLaminas($request, true);
        } catch (InvalidArgumentException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof InvalidUriPartException) {
                return RouteResult::fromRouteFailure(null);
            }
            throw $e;
        }

        $match = $this->laminasRouter->match($laminasRequest);

        if (null === $match) {
            // No route matched at all; to indicate that it's not due to the
            // request method, we specify any request method was allowed.
            return RouteResult::fromRouteFailure(Route::HTTP_METHOD_ANY);
        }

        return $this->marshalSuccessResultFromRouteMatch($match);
    }

    public function generateUri(string $name, array $substitutions = [], array $options = []): string
    {
        // Must inject routes prior to generating URIs.
        $this->injectRoutes();

        if (! $this->laminasRouter->hasRoute($name)) {
            throw new Exception\RuntimeException(sprintf(
                'Cannot generate URI based on route "%s"; route not found',
                $name
            ));
        }

        $name = $this->routeNameMap[$name] ?? $name;

        $options = array_merge($options, [
            'name'             => $name,
            'only_return_path' => true,
        ]);

        return $this->laminasRouter->assemble($substitutions, $options);
    }

    private function createRouter(): TreeRouteStack
    {
        return new TreeRouteStack();
    }

    /**
     * Create a successful RouteResult from the given RouteMatch.
     */
    private function marshalSuccessResultFromRouteMatch(RouteMatch $match): RouteResult
    {
        $params = $match->getParams();

        if (array_key_exists(self::METHOD_NOT_ALLOWED_ROUTE, $params)) {
            return RouteResult::fromRouteFailure(
                $this->allowedMethodsByPath[$params[self::METHOD_NOT_ALLOWED_ROUTE]]
            );
        }

        $routeName = $this->getMatchedRouteName($match->getMatchedRouteName());

        $route = array_reduce($this->routes, static function (?Route $matched, Route $route) use ($routeName): ?Route {
            if ($matched instanceof Route) {
                return $matched;
            }

            // We store the route name already, so we can match on that
            if ($routeName === $route->getName()) {
                return $route;
            }

            return null;
        }, null);

        if (null === $route) {
            // This should never happen, as Mezzio\Router\Route always
            // ensures a non-empty route name. Marking as failed route to be
            // consistent with other implementations.
            return RouteResult::fromRouteFailure(Route::HTTP_METHOD_ANY);
        }

        return RouteResult::fromRoute($route, $params);
    }

    /**
     * Create route configuration for matching one or more HTTP methods.
     */
    private function createHttpMethodRoute(Route $route): array
    {
        return [
            'type'    => 'method',
            'options' => [
                'verb'     => implode(',', $route->getAllowedMethods()),
                'defaults' => [
                    'middleware' => $route->getMiddleware(),
                ],
            ],
        ];
    }

    /**
     * Create the configuration for the "method not allowed" route.
     *
     * The specification is used for routes that have HTTP method negotiation;
     * essentially, this is a route that will always match, but *after* the
     * HTTP method route has already failed. By checking for this route later,
     * we can return a 405 response with the allowed methods.
     */
    private function createMethodNotAllowedRoute(string $path): array
    {
        return [
            'type'     => 'regex',
            'priority' => -1,
            'options'  => [
                'regex'    => '',
                'defaults' => [
                    self::METHOD_NOT_ALLOWED_ROUTE => $path,
                ],
                'spec'     => '',
            ],
        ];
    }

    /**
     * Calculate the route name.
     *
     * Routes will generally match the child HTTP method routes, which will not
     * match the names they were registered with; this method strips the method
     * route name if present.
     */
    private function getMatchedRouteName(string $name): string
    {
        // Check for <name>/GET:POST style route names; if so, strip off the
        // child route matching the method.
        if (preg_match('/(?P<name>.+)\/([!#$%&\'*+.^_`\|~0-9a-z-]+:?)+$/i', $name, $matches)) {
            return $matches['name'];
        }

        // Otherwise, just use the name.
        return rtrim($name, '/');
    }

    /**
     * Inject any unprocessed routes into the underlying router implementation.
     */
    private function injectRoutes(): void
    {
        foreach ($this->routesToInject as $index => $route) {
            $this->injectRoute($route);
            $this->routes[] = $route;
            unset($this->routesToInject[$index]);
        }
    }

    /**
     * Inject route into the underlying router implemetation.
     */
    private function injectRoute(Route $route): void
    {
        $name    = $route->getName();
        $path    = $route->getPath();
        $options = $route->getOptions();
        $options = array_replace_recursive($options, [
            'route'    => $path,
            'defaults' => [
                'middleware' => $route->getMiddleware(),
            ],
        ]);

        $allowedMethods = $route->getAllowedMethods();
        if (Route::HTTP_METHOD_ANY === $allowedMethods) {
            $this->laminasRouter->addRoute($name, [
                'type'    => 'segment',
                'options' => $options,
            ]);
            $this->routeNameMap[$name] = $name;
            return;
        }

        // Remove the middleware from the segment route in favor of method route
        unset($options['defaults']['middleware']);
        if (empty($options['defaults'])) {
            unset($options['defaults']);
        }

        $httpMethodRouteName   = implode(':', $allowedMethods);
        $httpMethodRoute       = $this->createHttpMethodRoute($route);
        $methodNotAllowedRoute = $this->createMethodNotAllowedRoute($path);

        $spec = [
            'type'          => 'segment',
            'options'       => $options,
            'may_terminate' => false,
            'child_routes'  => [
                $httpMethodRouteName           => $httpMethodRoute,
                self::METHOD_NOT_ALLOWED_ROUTE => $methodNotAllowedRoute,
            ],
        ];

        if (array_key_exists($path, $this->allowedMethodsByPath)) {
            $allowedMethods = array_merge($this->allowedMethodsByPath[$path], $allowedMethods);
            // Remove the method not allowed route as it is already present for the path
            unset($spec['child_routes'][self::METHOD_NOT_ALLOWED_ROUTE]);
        }

        $this->laminasRouter->addRoute($name, $spec);
        $this->allowedMethodsByPath[$path] = $allowedMethods;
        $this->routeNameMap[$name]         = sprintf('%s/%s', $name, $httpMethodRouteName);
    }
}
