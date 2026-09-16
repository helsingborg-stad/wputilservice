<?php

declare(strict_types=1);

namespace WpUtilService\Tests;

use PHPUnit\Framework\TestCase;
use WpUtilService\Config\EnqueueManagerConfig;

class EnqueueManagerConfigTest extends TestCase
{
    public function testConfigurationIsKeptOnTheInstance(): void
    {
        $config = new EnqueueManagerConfig();
        $config
            ->setRootDirectory('/var/www/wp-content/mu-plugins/example')
            ->setDistDirectory('/custom/dist/')
            ->setManifestName('custom-manifest.json')
            ->setCacheBustState(false);

        $this->assertSame('/var/www/wp-content/mu-plugins/example', $config->getRootDirectory());
        $this->assertSame('/custom/dist/', $config->getDistDirectory());
        $this->assertSame('custom-manifest.json', $config->getManifestName());
        $this->assertFalse($config->getIsCacheBustEnabled());
    }

    public function testConfigurationDoesNotLeakBetweenInstances(): void
    {
        $configured = new EnqueueManagerConfig();
        $configured
            ->setRootDirectory('/var/www/wp-content/plugins/first-plugin')
            ->setDistDirectory('/first-plugin/dist/')
            ->setManifestName('first-manifest.json')
            ->setCacheBustState(false);

        $fresh = new EnqueueManagerConfig();

        $this->assertSame('/assets/dist/', $fresh->getDistDirectory());
        $this->assertSame('manifest.json', $fresh->getManifestName());
        $this->assertTrue($fresh->getIsCacheBustEnabled());

        $this->expectException(\RuntimeException::class);
        $fresh->getRootDirectory();
    }
}
