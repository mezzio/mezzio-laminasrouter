<?php

declare(strict_types=1);

namespace MezzioTest\Router\LaminasRouter;

use Laminas\Diactoros\ServerRequest;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Uri\Exception\InvalidUriPartException;
use Mezzio\Router\LaminasRouter;
use Mezzio\Router\LaminasRouter\RouterRequest;
use Mezzio\Router\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;

final class RouterRequestTest extends TestCase
{
    public function testExposesUriAndMethodOfPsrRequest(): void
    {
        $request = new RouterRequest(new ServerRequest([], [], 'https://example.com:8443/foo/bar?baz=1', 'post'));

        self::assertSame('POST', $request->getMethod());
        self::assertSame('https', $request->getUri()->getScheme());
        self::assertSame('example.com', $request->getUri()->getHost());
        self::assertSame(8443, $request->getUri()->getPort());
        self::assertSame('/foo/bar', $request->getUri()->getPath());
        self::assertSame('baz=1', $request->getUri()->getQuery());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string, string}>
     */
    public static function baseUrlProvider(): iterable
    {
        yield 'no server params' => [[], '/foo', ''];
        yield 'front controller in document root' => [
            [
                'SCRIPT_FILENAME' => '/var/www/public/index.php',
                'SCRIPT_NAME'     => '/index.php',
                'PHP_SELF'        => '/index.php',
            ],
            '/foo',
            '',
        ];
        yield 'front controller in request path' => [
            ['SCRIPT_FILENAME' => 'index.php', 'SCRIPT_NAME' => '/index.php', 'PHP_SELF' => '/index.php'],
            '/index.php/foo',
            '/index.php',
        ];
        yield 'sub directory with rewrite' => [
            ['SCRIPT_FILENAME' => 'index.php', 'SCRIPT_NAME' => '/sub/index.php', 'PHP_SELF' => '/sub/index.php'],
            '/sub/foo?bar=1',
            '/sub',
        ];
        yield 'sub directory with front controller' => [
            ['SCRIPT_FILENAME' => 'index.php', 'SCRIPT_NAME' => '/sub/index.php', 'PHP_SELF' => '/sub/index.php'],
            'https://example.com/sub/index.php/foo',
            '/sub/index.php',
        ];
        yield 'sub directory from PHP_SELF' => [
            ['SCRIPT_FILENAME' => '/var/www/sub/index.php', 'PHP_SELF' => '/sub/index.php'],
            '/sub/foo',
            '/sub',
        ];
        yield 'sub directory from ORIG_SCRIPT_NAME' => [
            ['SCRIPT_FILENAME' => 'index.php', 'ORIG_SCRIPT_NAME' => '/sub/index.php'],
            '/sub/foo',
            '/sub',
        ];
        yield 'script name only in query string' => [
            ['SCRIPT_FILENAME' => 'index.php', 'SCRIPT_NAME' => '/sub/index.php', 'PHP_SELF' => '/sub/index.php'],
            '/other?next=/sub/index.php',
            '',
        ];
    }

    /**
     * @param array<string, mixed> $serverParams
     */
    #[DataProvider('baseUrlProvider')]
    public function testDetectsBaseUrlFromServerParams(array $serverParams, string $uri, string $expected): void
    {
        $request = new RouterRequest(new ServerRequest($serverParams, [], $uri, 'GET'));

        self::assertSame($expected, $request->getBaseUrl());
    }

    public function testMatchesRouteBelowDetectedBaseUrl(): void
    {
        $router = new LaminasRouter(new TreeRouteStack());
        $router->addRoute(new Route('/foo', $this->createMock(MiddlewareInterface::class), ['GET'], 'foo'));

        $result = $router->match(new ServerRequest(
            ['SCRIPT_FILENAME' => 'index.php', 'SCRIPT_NAME' => '/sub/index.php', 'PHP_SELF' => '/sub/index.php'],
            [],
            '/sub/foo',
            'GET'
        ));

        self::assertTrue($result->isSuccess());
        self::assertSame('foo', $result->getMatchedRouteName());
    }

    public function testInvalidUriIsRejected(): void
    {
        $this->expectException(InvalidUriPartException::class);

        new RouterRequest($this->createRequestWithUri('http://exa mple.com/foo'));
    }

    public function testRouterReturnsRouteFailureForInvalidUri(): void
    {
        $result = (new LaminasRouter())->match($this->createRequestWithUri('http://exa mple.com/foo'));

        self::assertTrue($result->isFailure());
        self::assertFalse($result->isMethodFailure());
    }

    private function createRequestWithUri(string $uri): ServerRequestInterface
    {
        $psrUri = $this->createMock(UriInterface::class);
        $psrUri->method('__toString')->willReturn($uri);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($psrUri);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getServerParams')->willReturn([]);

        return $request;
    }
}
