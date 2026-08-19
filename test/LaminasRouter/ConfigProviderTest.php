<?php

declare(strict_types=1);

namespace MezzioTest\Router\LaminasRouter;

use Mezzio\Router\LaminasRouter;
use Mezzio\Router\LaminasRouter\ConfigProvider;
use Mezzio\Router\LaminasRouterFactory;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;

final class ConfigProviderTest extends TestCase
{
    public function testReturnedArrayContainsDependencies(): void
    {
        $this->assertSame([
            'dependencies' => [
                'aliases'   => [
                    RouterInterface::class => LaminasRouter::class,
                ],
                'factories' => [
                    LaminasRouter::class => LaminasRouterFactory::class,
                ],
            ],
        ], (new ConfigProvider())->__invoke());
    }
}
