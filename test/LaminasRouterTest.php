<?php

/**
 * @see       https://github.com/mezzio/mezzio-laminasrouter for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-laminasrouter/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-laminasrouter/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Router;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Laminas\Diactoros\ServerRequest;
use Laminas\Http\Request as LaminasRequest;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Psr7Bridge\Psr7ServerRequest;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouteMatch;
use Mezzio\Router\LaminasRouter;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;

class LaminasRouterTest extends TestCase
{
    private $laminasRouter;

    protected function setUp()
    {
        $this->laminasRouter = $this->prophesize(TreeRouteStack::class);
    }

    private function getRouter() : LaminasRouter
    {
        return new LaminasRouter($this->laminasRouter->reveal());
    }

    private function getMiddleware() : MiddlewareInterface
    {
        return $this->prophesize(MiddlewareInterface::class)->reveal();
    }

    public function testWillLazyInstantiateALaminasTreeRouteStackIfNoneIsProvidedToConstructor()
    {
        $router = new LaminasRouter();
        $this->assertAttributeInstanceOf(TreeRouteStack::class, 'laminasRouter', $router);
    }

    public function createRequestProphecy($requestMethod = RequestMethod::METHOD_GET)
    {
        $request = $this->prophesize(ServerRequestInterface::class);

        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/foo');
        $uri->__toString()->willReturn('http://www.example.com/foo');

        $request->getMethod()->willReturn($requestMethod);
        $request->getUri()->will([$uri, 'reveal']);
        $request->getHeaders()->willReturn([]);
        $request->getCookieParams()->willReturn([]);
        $request->getQueryParams()->willReturn([]);
        $request->getServerParams()->willReturn([]);

        return $request;
    }

    public function testAddingRouteAggregatesInRouter()
    {
        $route = new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_GET]);
        $router = $this->getRouter();
        $router->addRoute($route);
        $this->assertAttributeContains($route, 'routesToInject', $router);
    }

    /**
     * @depends testAddingRouteAggregatesInRouter
     */
    public function testMatchingInjectsRoutesInRouter()
    {
        $middleware = $this->getMiddleware();
        $route = new Route('/foo', $middleware, [RequestMethod::METHOD_GET]);

        $this->laminasRouter->addRoute('/foo^GET', [
            'type' => 'segment',
            'options' => [
                'route' => '/foo',
            ],
            'may_terminate' => false,
            'child_routes' => [
                RequestMethod::METHOD_GET => [
                    'type' => 'method',
                    'options' => [
                        'verb' => RequestMethod::METHOD_GET,
                        'defaults' => [
                            'middleware' => $middleware,
                        ],
                    ],
                ],
                LaminasRouter::METHOD_NOT_ALLOWED_ROUTE => [
                    'type'     => 'regex',
                    'priority' => -1,
                    'options'  => [
                        'regex' => '',
                        'defaults' => [
                            LaminasRouter::METHOD_NOT_ALLOWED_ROUTE => '/foo',
                        ],
                        'spec' => '',
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $router = $this->getRouter();
        $router->addRoute($route);

        $request = $this->createRequestProphecy();
        $this->laminasRouter->match(Argument::type(LaminasRequest::class))->willReturn(null);

        $router->match($request->reveal());
    }

    /**
     * @depends testAddingRouteAggregatesInRouter
     */
    public function testGeneratingUriInjectsRoutesInRouter()
    {
        $middleware = $this->getMiddleware();
        $route = new Route('/foo', $middleware, [RequestMethod::METHOD_GET]);

        $this->laminasRouter->addRoute('/foo^GET', [
            'type' => 'segment',
            'options' => [
                'route' => '/foo',
            ],
            'may_terminate' => false,
            'child_routes' => [
                RequestMethod::METHOD_GET => [
                    'type' => 'method',
                    'options' => [
                        'verb' => RequestMethod::METHOD_GET,
                        'defaults' => [
                            'middleware' => $middleware,
                        ],
                    ],
                ],
                LaminasRouter::METHOD_NOT_ALLOWED_ROUTE => [
                    'type'     => 'regex',
                    'priority' => -1,
                    'options'  => [
                        'regex' => '',
                        'defaults' => [
                            LaminasRouter::METHOD_NOT_ALLOWED_ROUTE => '/foo',
                        ],
                        'spec' => '',
                    ],
                ],
            ],
        ])->shouldBeCalled();
        $this->laminasRouter->hasRoute('foo')->willReturn(true);
        $this->laminasRouter->assemble(
            [],
            [
                'name' => 'foo',
                'only_return_path' => true,
            ]
        )->willReturn('/foo');

        $router = $this->getRouter();
        $router->addRoute($route);

        $this->assertEquals('/foo', $router->generateUri('foo'));
    }

    public function testCanSpecifyRouteOptions()
    {
        $middleware = $this->getMiddleware();
        $route = new Route('/foo/:id', $middleware, [RequestMethod::METHOD_GET]);
        $route->setOptions([
            'constraints' => [
                'id' => '\d+',
            ],
            'defaults' => [
                'bar' => 'baz',
            ],
        ]);

        $this->laminasRouter->addRoute('/foo/:id^GET', [
            'type' => 'segment',
            'options' => [
                'route' => '/foo/:id',
                'constraints' => [
                    'id' => '\d+',
                ],
                'defaults' => [
                    'bar' => 'baz'
                ],
            ],
            'may_terminate' => false,
            'child_routes' => [
                RequestMethod::METHOD_GET => [
                    'type' => 'method',
                    'options' => [
                        'verb' => RequestMethod::METHOD_GET,
                        'defaults' => [
                            'middleware' => $middleware,
                        ],
                    ],
                ],
                LaminasRouter::METHOD_NOT_ALLOWED_ROUTE => [
                    'type'     => 'regex',
                    'priority' => -1,
                    'options'  => [
                        'regex' => '',
                        'defaults' => [
                            LaminasRouter::METHOD_NOT_ALLOWED_ROUTE => '/foo/:id',
                        ],
                        'spec' => '',
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $this->laminasRouter->hasRoute('foo')->willReturn(true);
        $this->laminasRouter->assemble(
            [],
            [
                'name' => 'foo',
                'only_return_path' => true,
            ]
        )->willReturn('/foo');

        $router = $this->getRouter();
        $router->addRoute($route);
        $router->generateUri('foo');
    }

    public function routeResults()
    {
        return [
            'success' => [
                new Route('/foo', 'bar'),
                RouteResult::fromRouteMatch('/foo', 'bar'),
            ],
            'failure' => [
                new Route('/foo', 'bar'),
                RouteResult::fromRouteFailure(),
            ],
        ];
    }

    public function testMatch()
    {
        $middleware = $this->getMiddleware();
        $route = new Route('/foo', $middleware, [RequestMethod::METHOD_GET]);
        $laminasRouter = new LaminasRouter();
        $laminasRouter->addRoute($route);

        $request = new ServerRequest(
            ['REQUEST_METHOD' => RequestMethod::METHOD_GET],
            [],
            '/foo',
            RequestMethod::METHOD_GET
        );

        $result = $laminasRouter->match($request);
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertEquals('/foo^GET', $result->getMatchedRouteName());
        $this->assertEquals($middleware, $result->getMatchedRoute()->getMiddleware());
    }

    public function testReturnsRouteFailureForRouteInjectedManuallyIntoBaseRouterButNotRouterBridge()
    {
        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn('/foo');

        $request = new ServerRequest(
            ['REQUEST_METHOD' => RequestMethod::METHOD_GET],
            [],
            '/foo',
            RequestMethod::METHOD_GET
        );
        $laminasRequest = Psr7ServerRequest::toLaminas($request);

        $routeMatch = new \Laminas\Router\Http\RouteMatch([], 4);
        $routeMatch->setMatchedRouteName('/foo');

        $this->laminasRouter->match($laminasRequest)->willReturn($routeMatch);

        $router = $this->getRouter();
        $result = $router->match($request);

        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertFalse($result->isMethodFailure());
    }

    public function testMatchedRouteNameNoAllowedMethods()
    {
        $middleware = $this->getMiddleware();

        $laminasRouter = new LaminasRouter();
        $laminasRouter->addRoute(new Route('/foo', $middleware, [], '/foo'));

        $request = new ServerRequest(
            ['REQUEST_METHOD' => RequestMethod::METHOD_HEAD],
            [],
            '/foo',
            RequestMethod::METHOD_HEAD
        );
        $result = $laminasRouter->match($request);
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->getMatchedRoute());
        $this->assertSame([], $result->getAllowedMethods());
    }

    public function testMatchedRouteNameWhenGetMethodAllowed()
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
        $result = $laminasRouter->match($request);
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertSame('/foo', $result->getMatchedRouteName());
        $this->assertSame($middleware, $result->getMatchedRoute()->getMiddleware());
    }

    /**
     * @group match
     */
    public function testSuccessfulMatchIsPossible()
    {
        $routeMatch = $this->prophesize(RouteMatch::class);
        $routeMatch->getMatchedRouteName()->willReturn('/foo');
        $routeMatch->getParams()->willReturn([
            'middleware' => 'bar',
        ]);

        $this->laminasRouter
            ->match(Argument::type(LaminasRequest::class))
            ->willReturn($routeMatch->reveal());
        $this->laminasRouter
            ->addRoute('/foo', Argument::type('array'))
            ->shouldBeCalled();

        $request = $this->createRequestProphecy();

        $middleware = $this->getMiddleware();
        $router = $this->getRouter();
        $router->addRoute(new Route('/foo', $middleware, [RequestMethod::METHOD_GET], '/foo'));
        $result = $router->match($request->reveal());
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertSame('/foo', $result->getMatchedRouteName());
        $this->assertSame($middleware, $result->getMatchedRoute()->getMiddleware());
    }

    /**
     * @group match
     */
    public function testNonSuccessfulMatchNotDueToHttpMethodsIsPossible()
    {
        $this->laminasRouter
            ->match(Argument::type(LaminasRequest::class))
            ->willReturn(null);

        $request = $this->createRequestProphecy();

        $router = $this->getRouter();
        $result = $router->match($request->reveal());
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertFalse($result->isMethodFailure());
    }

    /**
     * @group match
     */
    public function testMatchFailureDueToHttpMethodReturnsRouteResultWithAllowedMethods()
    {
        $router = new LaminasRouter();
        $router->addRoute(new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_POST, 'DELETE']));
        $request = new ServerRequest(
            ['REQUEST_METHOD' => RequestMethod::METHOD_GET],
            [],
            '/foo',
            RequestMethod::METHOD_GET
        );
        $result = $router->match($request);

        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertTrue($result->isMethodFailure());
        $this->assertEquals([RequestMethod::METHOD_POST, 'DELETE'], $result->getAllowedMethods());
    }

    /**
     * @group match
     */
    public function testMatchFailureDueToMethodNotAllowedWithParamsInTheRoute()
    {
        $router = new LaminasRouter();
        $router->addRoute(new Route('/foo[/:id]', $this->getMiddleware(), [RequestMethod::METHOD_POST, 'DELETE']));
        $request = new ServerRequest(
            ['REQUEST_METHOD' => RequestMethod::METHOD_GET],
            [],
            '/foo/1',
            RequestMethod::METHOD_GET
        );
        $result = $router->match($request);

        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertTrue($result->isMethodFailure());
        $this->assertEquals([RequestMethod::METHOD_POST, 'DELETE'], $result->getAllowedMethods());
    }

    /**
     * @group 53
     */
    public function testCanGenerateUriFromRoutes()
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

        $this->assertEquals('/foo', $router->generateUri('foo-create'));
        $this->assertEquals('/foo', $router->generateUri('foo-list'));
        $this->assertEquals('/foo/bar', $router->generateUri('foo', ['id' => 'bar']));
        $this->assertEquals('/bar/BAZ', $router->generateUri('bar', ['baz' => 'BAZ']));
    }

    /**
     * @group 3
     */
    public function testPassingTrailingSlashToRouteNotExpectingItResultsIn404FailureRouteResult()
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
        $result = $router->match($request);
        $this->assertTrue($result->isFailure());
        $this->assertFalse($result->isMethodFailure());
    }

    public function testSuccessfulMatchingComposesRouteInRouteResult()
    {
        $route = new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_GET]);

        $routeMatch = $this->prophesize(RouteMatch::class);
        $routeMatch->getMatchedRouteName()->willReturn($route->getName());
        $routeMatch->getParams()->willReturn([
            'middleware' => $route->getMiddleware(),
        ]);

        $this->laminasRouter
            ->match(Argument::type(LaminasRequest::class))
            ->willReturn($routeMatch->reveal());
        $this->laminasRouter
            ->addRoute('/foo^GET', Argument::type('array'))
            ->shouldBeCalled();

        $request = $this->createRequestProphecy();

        $router = $this->getRouter();
        $router->addRoute($route);

        $result = $router->match($request->reveal());

        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertSame($route, $result->getMatchedRoute());
    }

    public function implicitMethods()
    {
        return [
            'head'    => [RequestMethod::METHOD_HEAD],
            'options' => [RequestMethod::METHOD_OPTIONS],
        ];
    }

    /**
     * @dataProvider implicitMethods
     *
     * @param string $method
     */
    public function testRoutesCanMatchImplicitHeadAndOptionsRequests($method)
    {
        $route = new Route('/foo', $this->getMiddleware(), [RequestMethod::METHOD_PUT]);

        $router = new LaminasRouter();
        $router->addRoute($route);

        $request = $this->createRequestProphecy($method);
        $result = $router->match($request->reveal());

        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertSame([RequestMethod::METHOD_PUT], $result->getAllowedMethods());
    }

    public function testUriGenerationMayUseOptions()
    {
        $route = new Route('/de/{lang}', $this->getMiddleware(), [RequestMethod::METHOD_PUT], 'test');

        $router = new LaminasRouter();
        $router->addRoute($route);

        $translator = $this->prophesize(TranslatorInterface::class);
        $translator->translate('lang', 'uri', 'de')->willReturn('found');

        $uri = $router->generateUri('test', [], [
            'translator'  => $translator->reveal(),
            'locale'      => 'de',
            'text_domain' => 'uri',
        ]);

        $this->assertEquals('/de/found', $uri);
    }
}
