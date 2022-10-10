<?php

declare(strict_types=1);

namespace MezzioTest\Router;

use Closure;
use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Uri;
use Laminas\Http\Request as LaminasRequest;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Psr7Bridge\Psr7ServerRequest;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouteMatch;
use Mezzio\Router\Exception\RuntimeException;
use Mezzio\Router\LaminasRouter;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class LaminasRouterTest extends TestCase
{
    /** @var TreeRouteStack&MockObject */
    private TreeRouteStack $laminasRouter;

    protected function setUp(): void
    {
        $this->laminasRouter = $this->createMock(TreeRouteStack::class);
    }

    private function getRouter(): LaminasRouter
    {
        return new LaminasRouter($this->laminasRouter);
    }

    private function getMiddleware(): MiddlewareInterface
    {
        return $this->createMock(MiddlewareInterface::class);
    }

    public function testWillLazyInstantiateALaminasTreeRouteStackIfNoneIsProvidedToConstructor(): void
    {
        $router        = new LaminasRouter();
        $laminasRouter = Closure::bind(fn() => $this->laminasRouter, $router, LaminasRouter::class)();
        self::assertInstanceOf(TreeRouteStack::class, $laminasRouter);
    }

    private function createRequest(string $requestMethod = RequestMethod::METHOD_GET): ServerRequestInterface
    {
        $uri = new Uri('https://www.example.com/foo');

        return (new ServerRequestFactory())->createServerRequest($requestMethod, $uri);
    }

    public function testAddingRouteAggregatesInRouter(): void
    {
        $route  = new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_GET]);
        $router = $this->getRouter();
        $router->addRoute($route);

        /** @psalm-var Closure(): list<Route> $fn */
        $fn             = fn(): array => $this->routesToInject;
        $routesToInject = Closure::bind($fn, $router, LaminasRouter::class)();
        self::assertContains($route, $routesToInject);
    }

    /**
     * @depends testAddingRouteAggregatesInRouter
     */
    public function testMatchingInjectsRoutesInRouter(): void
    {
        $middleware = $this->getMiddleware();
        $route      = new Route('/foo', $middleware, [RequestMethod::METHOD_GET]);

        $this->laminasRouter->expects(self::once())
            ->method('addRoute')
            ->with('/foo^GET', [
                'type'          => 'segment',
                'options'       => [
                    'route' => '/foo',
                ],
                'may_terminate' => false,
                'child_routes'  => [
                    RequestMethod::METHOD_GET               => [
                        'type'    => 'method',
                        'options' => [
                            'verb'     => RequestMethod::METHOD_GET,
                            'defaults' => [
                                'middleware' => $middleware,
                            ],
                        ],
                    ],
                    LaminasRouter::METHOD_NOT_ALLOWED_ROUTE => [
                        'type'     => 'regex',
                        'priority' => -1,
                        'options'  => [
                            'regex'    => '',
                            'defaults' => [
                                LaminasRouter::METHOD_NOT_ALLOWED_ROUTE => '/foo',
                            ],
                            'spec'     => '',
                        ],
                    ],
                ],
            ]);

        $router = $this->getRouter();
        $router->addRoute($route);
        $request = $this->createRequest();
        $this->laminasRouter->expects(self::once())
            ->method('match')
            ->with(self::isInstanceOf(LaminasRequest::class))
            ->willReturn(null);

        $router->match($request);
    }

    /**
     * @depends testAddingRouteAggregatesInRouter
     */
    public function testGeneratingUriInjectsRoutesInRouter(): void
    {
        $middleware = $this->getMiddleware();
        $route      = new Route('/foo', $middleware, [RequestMethod::METHOD_GET]);

        $this->laminasRouter->expects(self::once())
            ->method('addRoute')
            ->with('/foo^GET', [
                'type'          => 'segment',
                'options'       => [
                    'route' => '/foo',
                ],
                'may_terminate' => false,
                'child_routes'  => [
                    RequestMethod::METHOD_GET               => [
                        'type'    => 'method',
                        'options' => [
                            'verb'     => RequestMethod::METHOD_GET,
                            'defaults' => [
                                'middleware' => $middleware,
                            ],
                        ],
                    ],
                    LaminasRouter::METHOD_NOT_ALLOWED_ROUTE => [
                        'type'     => 'regex',
                        'priority' => -1,
                        'options'  => [
                            'regex'    => '',
                            'defaults' => [
                                LaminasRouter::METHOD_NOT_ALLOWED_ROUTE => '/foo',
                            ],
                            'spec'     => '',
                        ],
                    ],
                ],
            ]);

        $this->laminasRouter->expects(self::once())
            ->method('hasRoute')
            ->with('foo')
            ->willReturn(true);
        $this->laminasRouter->expects(self::once())
            ->method('assemble')
            ->with(
                [],
                [
                    'name'             => 'foo',
                    'only_return_path' => true,
                ]
            )
            ->willReturn('/foo');

        $router = $this->getRouter();
        $router->addRoute($route);

        self::assertEquals('/foo', $router->generateUri('foo'));
    }

    public function testCanSpecifyRouteOptions(): void
    {
        $middleware = $this->getMiddleware();
        $route      = new Route('/foo/:id', $middleware, [RequestMethod::METHOD_GET]);
        $route->setOptions([
            'constraints' => [
                'id' => '\d+',
            ],
            'defaults'    => [
                'bar' => 'baz',
            ],
        ]);

        $this->laminasRouter->expects(self::once())
            ->method('addRoute')
            ->with('/foo/:id^GET', [
                'type'          => 'segment',
                'options'       => [
                    'route'       => '/foo/:id',
                    'constraints' => [
                        'id' => '\d+',
                    ],
                    'defaults'    => [
                        'bar' => 'baz',
                    ],
                ],
                'may_terminate' => false,
                'child_routes'  => [
                    RequestMethod::METHOD_GET               => [
                        'type'    => 'method',
                        'options' => [
                            'verb'     => RequestMethod::METHOD_GET,
                            'defaults' => [
                                'middleware' => $middleware,
                            ],
                        ],
                    ],
                    LaminasRouter::METHOD_NOT_ALLOWED_ROUTE => [
                        'type'     => 'regex',
                        'priority' => -1,
                        'options'  => [
                            'regex'    => '',
                            'defaults' => [
                                LaminasRouter::METHOD_NOT_ALLOWED_ROUTE => '/foo/:id',
                            ],
                            'spec'     => '',
                        ],
                    ],
                ],
            ]);

        $this->laminasRouter->expects(self::once())
            ->method('hasRoute')
            ->with('foo')
            ->willReturn(true);
        $this->laminasRouter->expects(self::once())
            ->method('assemble')
            ->with(
                [],
                [
                    'name'             => 'foo',
                    'only_return_path' => true,
                ]
            )
            ->willReturn('/foo');

        $router = $this->getRouter();
        $router->addRoute($route);
        $router->generateUri('foo');
    }

    public function testMatch(): void
    {
        $middleware    = $this->getMiddleware();
        $route         = new Route('/foo', $middleware, [RequestMethod::METHOD_GET]);
        $laminasRouter = new LaminasRouter();
        $laminasRouter->addRoute($route);

        $request = new ServerRequest(
            ['REQUEST_METHOD' => RequestMethod::METHOD_GET],
            [],
            '/foo',
            RequestMethod::METHOD_GET
        );

        $result = $laminasRouter->match($request);
        self::assertInstanceOf(RouteResult::class, $result);
        self::assertEquals('/foo^GET', $result->getMatchedRouteName());
        self::assertEquals($middleware, $result->getMatchedRoute()->getMiddleware());
    }

    public function testReturnsRouteFailureForRouteInjectedManuallyIntoBaseRouterButNotRouterBridge(): void
    {
        $request        = $this->createRequest();
        $laminasRequest = Psr7ServerRequest::toLaminas($request);

        $routeMatch = new \Laminas\Router\Http\RouteMatch([], 4);
        $routeMatch->setMatchedRouteName('/foo');

        $this->laminasRouter->expects(self::once())
            ->method('match')
            ->with($laminasRequest)
            ->willReturn($routeMatch);

        $router = $this->getRouter();
        $result = $router->match($request);

        self::assertInstanceOf(RouteResult::class, $result);
        self::assertTrue($result->isFailure());
        self::assertFalse($result->isMethodFailure());
    }

    public function testMatchedRouteNameWhenGetMethodAllowed(): void
    {
        $middleware = $this->getMiddleware();

        $laminasRouter = new LaminasRouter();
        $laminasRouter->addRoute(new Route('/foo', $middleware, [RequestMethod::METHOD_GET], '/foo'));

        $request = new ServerRequest(
            ['REQUEST_METHOD' => RequestMethod::METHOD_GET],
            [],
            '/foo',
            RequestMethod::METHOD_GET
        );
        $result  = $laminasRouter->match($request);
        self::assertInstanceOf(RouteResult::class, $result);
        self::assertTrue($result->isSuccess());
        self::assertSame('/foo', $result->getMatchedRouteName());
        self::assertSame($middleware, $result->getMatchedRoute()->getMiddleware());
    }

    /**
     * @group match
     */
    public function testSuccessfulMatchIsPossible(): void
    {
        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->expects(self::once())
            ->method('getMatchedRouteName')
            ->willReturn('/foo');
        $routeMatch->expects(self::once())
            ->method('getParams')
            ->willReturn([
                'middleware' => 'bar',
            ]);

        $this->laminasRouter->expects(self::once())
            ->method('match')
            ->with(self::isInstanceOf(LaminasRequest::class))
            ->willReturn($routeMatch);

        $this->laminasRouter->expects(self::once())
            ->method('addRoute')
            ->with('/foo', self::callback(function ($arg): bool {
                self::assertIsArray($arg);

                return true;
            }));

        $request = $this->createRequest();

        $middleware = $this->getMiddleware();
        $router     = $this->getRouter();
        $router->addRoute(new Route('/foo', $middleware, [RequestMethod::METHOD_GET], '/foo'));
        $result = $router->match($request);
        self::assertInstanceOf(RouteResult::class, $result);
        self::assertTrue($result->isSuccess());
        self::assertSame('/foo', $result->getMatchedRouteName());
        self::assertSame($middleware, $result->getMatchedRoute()->getMiddleware());
    }

    /**
     * @group match
     */
    public function testNonSuccessfulMatchNotDueToHttpMethodsIsPossible(): void
    {
        $this->laminasRouter->expects(self::once())
            ->method('match')
            ->with(self::isInstanceOf(LaminasRequest::class))
            ->willReturn(null);

        $request = $this->createRequest();

        $router = $this->getRouter();
        $result = $router->match($request);
        self::assertInstanceOf(RouteResult::class, $result);
        self::assertTrue($result->isFailure());
        self::assertFalse($result->isMethodFailure());
    }

    /**
     * @group match
     */
    public function testMatchFailureDueToHttpMethodReturnsRouteResultWithAllowedMethods(): void
    {
        $router = new LaminasRouter();
        $router->addRoute(new Route(
            '/foo',
            $this->getMiddleware(),
            [RequestMethod::METHOD_POST, RequestMethod::METHOD_DELETE]
        ));
        $request = new ServerRequest(
            ['REQUEST_METHOD' => RequestMethod::METHOD_GET],
            [],
            '/foo',
            RequestMethod::METHOD_GET
        );
        $result  = $router->match($request);

        self::assertInstanceOf(RouteResult::class, $result);
        self::assertTrue($result->isFailure());
        self::assertTrue($result->isMethodFailure());
        self::assertEquals([RequestMethod::METHOD_POST, RequestMethod::METHOD_DELETE], $result->getAllowedMethods());
    }

    /**
     * @group match
     */
    public function testMatchFailureDueToMethodNotAllowedWithParamsInTheRoute(): void
    {
        $router = new LaminasRouter();
        $router->addRoute(new Route(
            '/foo[/:id]',
            $this->getMiddleware(),
            [RequestMethod::METHOD_POST, RequestMethod::METHOD_DELETE]
        ));
        $request = new ServerRequest(
            ['REQUEST_METHOD' => RequestMethod::METHOD_GET],
            [],
            '/foo/1',
            RequestMethod::METHOD_GET
        );
        $result  = $router->match($request);

        self::assertInstanceOf(RouteResult::class, $result);
        self::assertTrue($result->isFailure());
        self::assertTrue($result->isMethodFailure());
        self::assertEquals([RequestMethod::METHOD_POST, RequestMethod::METHOD_DELETE], $result->getAllowedMethods());
    }

    /**
     * @group 53
     */
    public function testCanGenerateUriFromRoutes(): void
    {
        $router = new LaminasRouter();
        $route1 = new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_POST], 'foo-create');
        $route2 = new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'foo-list');
        $route3 = new Route('/foo/:id', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'foo');
        $route4 = new Route('/bar/:baz', $this->getMiddleware(), Route::HTTP_METHOD_ANY, 'bar');

        $router->addRoute($route1);
        $router->addRoute($route2);
        $router->addRoute($route3);
        $router->addRoute($route4);

        self::assertEquals('/foo', $router->generateUri('foo-create'));
        self::assertEquals('/foo', $router->generateUri('foo-list'));
        self::assertEquals('/foo/bar', $router->generateUri('foo', ['id' => 'bar']));
        self::assertEquals('/bar/BAZ', $router->generateUri('bar', ['baz' => 'BAZ']));
    }

    /**
     * @group 3
     */
    public function testPassingTrailingSlashToRouteNotExpectingItResultsIn404FailureRouteResult(): void
    {
        $router = new LaminasRouter();
        $route  = new Route('/api/ping', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'ping');

        $router->addRoute($route);
        $request = new ServerRequest(
            ['REQUEST_METHOD' => RequestMethod::METHOD_GET],
            [],
            '/api/ping/',
            RequestMethod::METHOD_GET
        );
        $result  = $router->match($request);
        self::assertTrue($result->isFailure());
        self::assertFalse($result->isMethodFailure());
    }

    public function testSuccessfulMatchingComposesRouteInRouteResult(): void
    {
        $route = new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_GET]);

        $routeMatch = $this->createMock(RouteMatch::class);
        $routeMatch->expects(self::once())
            ->method('getMatchedRouteName')
            ->willReturn($route->getName());

        $routeMatch->expects(self::once())
            ->method('getParams')
            ->willReturn([
                'middleware' => $route->getMiddleware(),
            ]);

        $this->laminasRouter->expects(self::once())
            ->method('match')
            ->with(self::isInstanceOf(LaminasRequest::class))
            ->willReturn($routeMatch);
        $this->laminasRouter->expects(self::once())
            ->method('addRoute')
            ->with('/foo^GET', self::callback(static function (mixed $arg): bool {
                self::assertIsArray($arg);

                return true;
            }));

        $request = $this->createRequest();

        $router = $this->getRouter();
        $router->addRoute($route);

        $result = $router->match($request);

        self::assertInstanceOf(RouteResult::class, $result);
        self::assertTrue($result->isSuccess());
        self::assertSame($route, $result->getMatchedRoute());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function implicitMethods(): array
    {
        return [
            'head'    => [RequestMethod::METHOD_HEAD],
            'options' => [RequestMethod::METHOD_OPTIONS],
        ];
    }

    /**
     * @dataProvider implicitMethods
     */
    public function testRoutesCanMatchImplicitHeadAndOptionsRequests(string $method): void
    {
        $route = new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_PUT]);

        $router = new LaminasRouter();
        $router->addRoute($route);

        $request = $this->createRequest($method);
        $result  = $router->match($request);

        self::assertInstanceOf(RouteResult::class, $result);
        self::assertFalse($result->isSuccess());
        self::assertSame([RequestMethod::METHOD_PUT], $result->getAllowedMethods());
    }

    public function testUriGenerationMayUseOptions(): void
    {
        $route = new Route('/de/{lang}', $this->getMiddleware(), [RequestMethod::METHOD_PUT], 'test');

        $router = new LaminasRouter();
        $router->addRoute($route);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())
            ->method('translate')
            ->with('lang', 'uri', 'de')
            ->willReturn('found');

        $uri = $router->generateUri('test', [], [
            'translator'  => $translator,
            'locale'      => 'de',
            'text_domain' => 'uri',
        ]);

        self::assertEquals('/de/found', $uri);
    }

    public function testGenerateUriRaisesExceptionForNotFoundRoute(): void
    {
        $router = new LaminasRouter();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('route not found');
        $router->generateUri('foo');
    }

    public function testMatchReturnsRouteFailureOnFailureToConvertPsr7Request(): void
    {
        $route = new Route('/some/path', $this->getMiddleware(), [RequestMethod::METHOD_GET], 'test');

        $router = new LaminasRouter();
        $router->addRoute($route);

        $serverRequest = (new ServerRequest())
            ->withUri(new Uri('https://${ip}/some/path'))
            ->withHeader('Host', '${ip}')
            ->withHeader('Accept', 'application/json');

        $result = $router->match($serverRequest);

        self::assertTrue($result->isFailure());
    }
}
