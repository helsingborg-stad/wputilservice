<?php

declare(strict_types=1);

namespace WpUtilService\Tests;

use PHPUnit\Framework\TestCase;
use WpUtilService\Features\RuntimeContextManager;

class RuntimeContextManagerTest extends TestCase
{
    public function testTooLongPathIsNormalized()
    {
        $context = new RuntimeContextManager();
        $context->setPath('/root/wp-content/plugins/my-plugin/src/php');
        $this->assertEquals('/root/wp-content/plugins/my-plugin/', $context->getNormalizedRootPath());
    }

    public function testMuPluginNestedPathIsNormalizedToPluginRoot(): void
    {
        // Arrange
        $context = new RuntimeContextManager();
        $context->setPath('/var/www/prod/wp-content/mu-plugins/acf-openstreetmap-field/Modularity');

        // Act
        $normalizedPath = $context->getNormalizedRootPath();

        // Assert
        $this->assertSame('/var/www/prod/wp-content/mu-plugins/acf-openstreetmap-field/', $normalizedPath);
    }
}
