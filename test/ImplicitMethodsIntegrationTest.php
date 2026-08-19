<?php

declare(strict_types=1);

namespace MezzioTest\Router;

use Generator;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RoutePluginManager;
use Mezzio\Router\LaminasRouter;
use Mezzio\Router\RouterInterface;
use Mezzio\Router\Test\AbstractImplicitMethodsIntegrationTest as RouterIntegrationTest;
use Psr\Container\ContainerInterface;

final class ImplicitMethodsIntegrationTest extends RouterIntegrationTest
{
    public function getRouter(): RouterInterface
    {
        return new LaminasRouter(
            new TreeRouteStack(new RoutePluginManager($this->createMock(ContainerInterface::class)))
        );
    }

    public static function implicitRoutesAndRequests(): Generator
    {
        $options = [
            'constraints' => [
                'version' => '\d+',
            ],
        ];

        // @codingStandardsIgnoreStart
        //                  route                route options, request       params
        yield 'static'  => ['/api/v1/me',        $options,      '/api/v1/me', []];
        yield 'dynamic' => ['/api/v:version/me', $options,      '/api/v3/me', ['version' => '3']];
        // @codingStandardsIgnoreEnd
    }
}
