<?php

declare(strict_types=1);

namespace Mezzio\Router\LaminasRouter;

use Laminas\ServiceManager\ServiceManager;
use Mezzio\Router\LaminasRouter;
use Mezzio\Router\LaminasRouterFactory;
use Mezzio\Router\RouterInterface;

/**
 * Provide base configuration for using the component.
 *
 * @see ConfigInterface
 *
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 * @psalm-type RouterConfigShape = array{
 *      dependencies: ServiceManagerConfiguration
 *  }
 */
final readonly class ConfigProvider
{
    /**
     * Provide default configuration.
     *
     * @return RouterConfigShape
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /**
     * Provide default container dependency configuration.
     *
     * @return ServiceManagerConfiguration
     */
    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                RouterInterface::class => LaminasRouter::class,
            ],
            'factories' => [
                LaminasRouter::class => LaminasRouterFactory::class,
            ],
        ];
    }
}
