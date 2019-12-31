<?php

/**
 * @see       https://github.com/mezzio/mezzio-laminasrouter for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-laminasrouter/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-laminasrouter/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Router;

use Generator;
use Mezzio\Router\LaminasRouter;
use Mezzio\Router\RouterInterface;
use Mezzio\Router\Test\ImplicitMethodsIntegrationTest as RouterIntegrationTest;

class ImplicitMethodsIntegrationTest extends RouterIntegrationTest
{
    public function getRouter() : RouterInterface
    {
        return new LaminasRouter();
    }

    public function implicitRoutesAndRequests() : Generator
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
