<?php

/**
 * @see       https://github.com/mezzio/mezzio-laminasrouter for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-laminasrouter/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-laminasrouter/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Router\LaminasRouter;

use Mezzio\Router\LaminasRouter;
use Mezzio\Router\LaminasRouter\ConfigProvider;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    /**
     * @var ConfigProvider
     */
    private $provider;

    protected function setUp() : void
    {
        $this->provider = new ConfigProvider();
    }

    public function testInvocationReturnsArray() : array
    {
        $config = ($this->provider)();
        $this->assertInternalType('array', $config);

        return $config;
    }

    /**
     * @depends testInvocationReturnsArray
     */
    public function testReturnedArrayContainsDependencies(array $config) : void
    {
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertInternalType('array', $config['dependencies']);

        $this->assertArrayHasKey('aliases', $config['dependencies']);
        $this->assertInternalType('array', $config['dependencies']['aliases']);
        $this->assertArrayHasKey(RouterInterface::class, $config['dependencies']['aliases']);

        $this->assertArrayHasKey('invokables', $config['dependencies']);
        $this->assertInternalType('array', $config['dependencies']['invokables']);
        $this->assertArrayHasKey(LaminasRouter::class, $config['dependencies']['invokables']);
    }
}
