<?php

declare(strict_types=1);

namespace Mezzio\Router;

use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RoutePluginManager;
use Psr\Container\ContainerInterface;

final readonly class LaminasRouterFactory
{
    public function __invoke(ContainerInterface $container): LaminasRouter
    {
        return new LaminasRouter(new TreeRouteStack(new RoutePluginManager($container)));
    }
}
