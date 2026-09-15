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
        $context = new RuntimeContextManager();
        $context->setPath('/var/www/wp-content/mu-plugins/acf-openstreetmap-field/Modularity');

        $this->assertSame(
            '/var/www/wp-content/mu-plugins/acf-openstreetmap-field/',
            $context->getNormalizedRootPath(),
        );
    }

    public function testThemeNestedPathIsNormalizedToThemeRoot(): void
    {
        $context = new RuntimeContextManager();
        $context->setPath('/var/www/wp-content/themes/municipio/vendor/package');

        $this->assertSame('/var/www/wp-content/themes/municipio/', $context->getNormalizedRootPath());
    }
}
