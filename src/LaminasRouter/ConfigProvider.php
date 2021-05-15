<?php

declare(strict_types=1);

namespace Mezzio\Router\LaminasRouter;

use Mezzio\Router\LaminasRouter;
use Mezzio\Router\RouterInterface;
use Zend\Expressive\Router\ZendRouter;

class ConfigProvider
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
            'aliases'    => [
                RouterInterface::class => LaminasRouter::class,

                // Legacy Zend Framework aliases
                \Zend\Expressive\Router\RouterInterface::class => RouterInterface::class,
                ZendRouter::class                              => LaminasRouter::class,
            ],
            'invokables' => [
                LaminasRouter::class => LaminasRouter::class,
            ],
        ];
    }
}
