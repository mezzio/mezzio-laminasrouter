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
            ],
            'factories' => [
                LaminasRouter::class => LaminasRouterFactory::class,
            ],
        ];
    }
}
