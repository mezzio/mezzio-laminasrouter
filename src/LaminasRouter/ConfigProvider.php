<?php

declare(strict_types=1);

namespace Mezzio\Router\LaminasRouter;

use Mezzio\Router\LaminasRouter;
use Mezzio\Router\LaminasRouterFactory;
use Mezzio\Router\RouterInterface;

final readonly class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                RouterInterface::class => LaminasRouter::class,

                // Legacy Zend Framework aliases
                'Zend\Expressive\Router\RouterInterface' => RouterInterface::class,
                'Zend\Expressive\Router\ZendRouter'      => LaminasRouter::class,
            ],
            'factories' => [
                LaminasRouter::class => LaminasRouterFactory::class,
            ],
        ];
    }
}
