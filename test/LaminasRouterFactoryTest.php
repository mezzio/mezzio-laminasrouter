<?php

declare(strict_types=1);

namespace MezzioTest\Router;

use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\Router\ConfigProvider as RouterConfigProvider;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\Router\LaminasRouter;
use Mezzio\Router\LaminasRouter\ConfigProvider as LaminasRouterConfigProvider;
use Mezzio\Router\LaminasRouterFactory;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 */
final class LaminasRouterFactoryTest extends TestCase
{
    public function testFactoryCreatesFunctionalLaminasRouter(): void
    {
        self::assertInstanceOf(
            LaminasRouter::class,
            (new LaminasRouterFactory())($this->createContainer())
        );
    }

    private function createContainer(): ServiceManager
    {
        $aggregator = new ConfigAggregator([
            RouterConfigProvider::class,
            LaminasRouterConfigProvider::class,
        ]);

        /** @psalm-var ServiceManagerConfiguration $dependencies */
        $dependencies = $aggregator->getMergedConfig()['dependencies'] ?? [];

        return new ServiceManager($dependencies);
    }
}
