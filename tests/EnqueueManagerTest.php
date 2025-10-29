<?php

declare(strict_types=1);

namespace WpUtilService\Tests;

use PHPUnit\Framework\TestCase;
use WpUtilService\Features\EnqueueManager;
use WpUtilService\Features\EnqueueAssetContext;
use WpService\Implementations\FakeWpService;

/**
 * @covers \WpUtilService\Features\EnqueueManager
 * @covers \WpUtilService\Features\EnqueueAssetContext
 */
class EnqueueManagerTest extends TestCase
{
    public function testFluentApiChaining()
    {
        //Setup deps
        $wpService = $this->getWpService();
        $manager = new EnqueueManager($wpService);
        $manager->setDistDirectory('/path/to/dist');
        
        // Test chaining add and with()->translation
        $result = $manager
            ->add('main.js', ['jquery'], '1.0.0', true)
            ->with()->translation(['localization_a' => ['Test']])
            ->add('second.js', [], '1.0.0', true)
            ->with()->data(['id' => 1])
            ->with()->translation(['localization_b' => ['Test']])
            ->add('secondary.js');

        $this->assertInstanceOf(EnqueueManager::class, $result);
    }

    public function testWithThrowsIfNoAssetAdded()
    {
        $manager = new EnqueueManager(
            $this->getWpService()
        );

        $this->expectException(\RuntimeException::class);
        $manager->with();
    }

    public function testContextReturnsManager()
    {
        $manager = new EnqueueManager(
            $this->getWpService()
        );

        // Adds a main.js asset
        $manager->add('main.js');

        //Test that with() returns a context object
        $context = $manager->with();
        $this->assertInstanceOf(EnqueueAssetContext::class, $context);

        // Test that translation() returns the manager
        $result = $context->translation(['key' => ['value']]);
        $this->assertInstanceOf(EnqueueManager::class, $result);
    }

    /**
     * Get a mock WordPress service for testing.
     *
     * @return FakeWpService
     */
    private function getWpService(): FakeWpService
    {
        return new FakeWpService([
            'getTemplateDirectoryUri' => fn() => '/path/to/templates',
            'wpRegisterScript'        => fn($handle, $src, $deps, $inFooter, $module) => true,
            'wpLocalizeScript'        => fn($handle, $objectName, $l10n) => true,
            'addFilter'               => fn($hook, $callback) => true
        ]);
    }
}
