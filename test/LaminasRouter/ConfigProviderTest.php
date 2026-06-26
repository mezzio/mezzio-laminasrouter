<?php

declare(strict_types=1);

namespace MezzioTest\Router\LaminasRouter;

use Mezzio\Router\LaminasRouter;
use Mezzio\Router\LaminasRouter\ConfigProvider;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;

final class ConfigProviderTest extends TestCase
{
    private ConfigProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new ConfigProvider();
    }

    public function testReturnedArrayContainsDependencies(): void
    {
        $config = ($this->provider)();
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertIsArray($config['dependencies']);

        $this->assertArrayHasKey('aliases', $config['dependencies']);
        $this->assertIsArray($config['dependencies']['aliases']);
        $this->assertArrayHasKey(RouterInterface::class, $config['dependencies']['aliases']);

        $this->assertArrayHasKey('factories', $config['dependencies']);
        $this->assertIsArray($config['dependencies']['factories']);
        $this->assertArrayHasKey(LaminasRouter::class, $config['dependencies']['factories']);
    }
}
