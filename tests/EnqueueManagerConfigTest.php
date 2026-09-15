<?php

declare(strict_types=1);

namespace WpUtilService\Tests;

use PHPUnit\Framework\TestCase;
use WpUtilService\Config\EnqueueManagerConfig;

class EnqueueManagerConfigTest extends TestCase
{
    public function testConfigDoesNotLeakStateBetweenInstances(): void
    {
        // Arrange
        $firstConfig = new EnqueueManagerConfig();
        $firstConfig
            ->setRootDirectory('/var/www/prod/wp-content/mu-plugins/acf-openstreetmap-field/Modularity')
            ->setDistDirectory('/Modularity/assets/dist/')
            ->setManifestName('custom-manifest.json')
            ->setCacheBustState(false);

        $secondConfig = new EnqueueManagerConfig();

        // Act
        $secondDistDirectory = $secondConfig->getDistDirectory();
        $secondManifestName = $secondConfig->getManifestName();
        $secondCacheBustState = $secondConfig->getIsCacheBustEnabled();

        // Assert
        $this->assertSame('/assets/dist/', $secondDistDirectory);
        $this->assertSame('manifest.json', $secondManifestName);
        $this->assertTrue($secondCacheBustState);
    }

    public function testGetRootDirectoryThrowsWhenNotSetPerInstance(): void
    {
        // Arrange
        $firstConfig = new EnqueueManagerConfig();
        $firstConfig->setRootDirectory('/var/www/prod/wp-content/mu-plugins/acf-openstreetmap-field/Modularity');

        $secondConfig = new EnqueueManagerConfig();

        // Assert
        $this->expectException(\RuntimeException::class);

        // Act
        $secondConfig->getRootDirectory();
    }
}
