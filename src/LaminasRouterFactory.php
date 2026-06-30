<?php

declare(strict_types=1);

namespace Mezzio\Router;

use Laminas\Router\Http\TreeRouteStack;
use Psr\Container\ContainerInterface;

use function assert;

final readonly class LaminasRouterFactory
{
    public function __invoke(ContainerInterface $container): LaminasRouter
    {
        $treeRouteStack = $container->get(TreeRouteStack::class);

        assert($treeRouteStack instanceof TreeRouteStack);

        return new LaminasRouter($treeRouteStack);
    }
}
