<?php

/**
 * @see       https://github.com/mezzio/mezzio-laminasrouter for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-laminasrouter/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-laminasrouter/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Router\LaminasRouter;

use Mezzio\Router\LaminasRouter;
use Mezzio\Router\RouterInterface;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies() : array
    {
        return [
            'aliases' => [
                RouterInterface::class => LaminasRouter::class,

                // Legacy Zend Framework aliases
                \Zend\Expressive\Router\RouterInterface::class => RouterInterface::class,
                \Zend\Expressive\Router\ZendRouter::class => LaminasRouter::class,
            ],
            'invokables' => [
                LaminasRouter::class => LaminasRouter::class,
            ],
        ];
    }
}
