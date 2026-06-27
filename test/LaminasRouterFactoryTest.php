<?php

declare(strict_types=1);

namespace MezzioTest\Router;

use Laminas\Router\ConfigProvider as RouterConfigProvider;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\Router\LaminasRouter;
use Mezzio\Router\LaminasRouter\ConfigProvider as LaminasRouterConfigProvider;
use Mezzio\Router\LaminasRouterFactory;
use PHPUnit\Framework\TestCase;

use function array_merge;
use function array_merge_recursive;

final class LaminasRouterFactoryTest extends TestCase
{
    public function testFactoryCreatesFunctionalLaminasRouter(): void
    {
        $container = $this->createContainer();

        $router = (new LaminasRouterFactory())($container);

        self::assertInstanceOf(LaminasRouter::class, $router);
    }

    private function createContainer(): ServiceManager
    {
        $dependencies = array_merge_recursive(
            (new RouterConfigProvider())->__invoke(),
            (new LaminasRouterConfigProvider())->__invoke(),
        );

        return new ServiceManager(array_merge($dependencies, $dependencies['dependencies']));
    }
}
