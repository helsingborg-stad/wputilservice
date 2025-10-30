<?php

declare(strict_types=1);

namespace WpUtilService\Tests;

use PHPUnit\Framework\TestCase;
use WpUtilService\Features\EnqueueManager;
use WpUtilService\Features\EnqueueAssetContext;
use WpUtilService\Tests\FakeWpService;

class EnqueueManagerTest extends TestCase
{
    public function testFluentApiChaining()
    {
        //Setup deps
        $manager = new EnqueueManager(
            $this->getWpService()
        );
        $manager->setDistDirectory('/path/to/dist');

        // Test chaining add and with()->translation
        $result = $manager
            ->add('main.js', ['jquery'], '1.0.0', true)
              ->with()
                ->translation('objectName', [
                  'localization_a' => ['Test']
                ])
              ->and()
                ->data([
                  'id' => 1
                ]);

        $this->assertInstanceOf(EnqueueManager::class, $result);
    }

    public function testFluentApiChainingWithParametizedTranslation()
    {
        //Setup deps
        $manager = new EnqueueManager(
            $this->getWpService()
        );
        $manager->setDistDirectory('/path/to/dist');

        // Test chaining add and with('translation')->
        $result = $manager
            ->add('main.js', ['jquery'], '1.0.0', true)
              ->with('translation', 'objectName', [
                  'localization_a' => ['Test']
              ]);

        $this->assertInstanceOf(EnqueueManager::class, $result);
    }

    public function testComplexFluentApiChainingWithParametizedTranslation()
    {
        //Setup deps
        $manager = new EnqueueManager(
            $this->getWpService()
        );
        $manager->setDistDirectory('/path/to/dist');

        // Test chaining add and with('translation')->
        $result = $manager
            ->add('main.js', ['jquery'], '1.0.0', true)
              ->with('translation', 'objectName', [
                  'localization_a' => ['Test']
              ])->and()->data([
                  'id' => 1
              ]);

        $this->assertInstanceOf(EnqueueManager::class, $result);
    }

    public function testComplexFluentApiChainingWithParametizedTranslationAndData()
    {
        //Setup deps
        $manager = new EnqueueManager(
            $this->getWpService()
        );
        $manager->setDistDirectory('/path/to/dist');

        // Test chaining add and with('translation')->
        $result = $manager
            ->add('main.js', ['jquery'], '1.0.0', true)
              ->with('translation', 'objectName', [
                  'localization_a' => ['Test']
              ])->with('data', [
                  'id' => 1
              ])->with('data', [
                  'id' => 1
              ]);

        $this->assertInstanceOf(EnqueueManager::class, $result);
    }

    public function testComplexFluentApiChaining() {

        //Setup deps
        $manager = new EnqueueManager(
            $this->getWpService()
        );
        $manager->setDistDirectory('/path/to/dist');

        $result = $manager
            ->add('main.js', ['jquery'], '1.0.0', true)
              ->with()->translation('objectName', [
                'localization_a' => ['Test']
              ])
            ->add('second.js', [], '1.0.0', true)
              ->with()->data([
                'id' => 1
              ])
              ->and()->translation('objectName2', [
                'localization_b' => ['Test']
              ])
            ->add('secondary.js');

        $this->assertInstanceOf(EnqueueManager::class, $result);
    }

    public function testAndThrowsIfUsedBeforeWith() {
        $manager = new EnqueueManager(
            $this->getWpService()
        );
        $manager->setDistDirectory('/path/to/dist');

        $this->expectException(\RuntimeException::class);
        $manager->add('main.js', ['jquery'], '1.0.0', true)->and();
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
        $result = $context->translation('objectName', ['key' => ['value']]);
        $this->assertInstanceOf(EnqueueManager::class, $result);
    }

    public function testThrowsIfObjectNameIsNotUnique()
    {
        $manager = new EnqueueManager(
            $this->getWpService()
        );

        $manager->add('main.js')->with()->translation('objectName', ['key' => ['value']]) ;
        $this->expectException(\RuntimeException::class);
        $manager->add('second.js')->with()->translation('objectName', ['key' => ['value']]);
    }
    

    public function testThrowsIfTranslationIsAddedOnAssetWithoutAbility()
    {
        $manager = new EnqueueManager(
            $this->getWpService()
        );

        $manager->add('main.css');
        $this->expectException(\RuntimeException::class);
        $manager->with()->translation('objectName', ['key' => ['value']]);
    }

    public function testThrowsIfDataIsAddedOnAssetWithoutAbility()
    {
        $manager = new EnqueueManager(
            $this->getWpService()
        );

        $manager->add('main.css');
        $this->expectException(\RuntimeException::class);
        $manager->with()->data(['key' => 'value']);
    }

    public function testWpRegisterScriptIsCalledWhenAddingScriptAndCarrysNormalizedParams()
    {
        $wpService = $this->getWpService();
        $manager = new EnqueueManager($wpService);
        $manager->setDistDirectory('/path/to/dist');

        $manager->add('main.js', ['jquery'], '1.0.0', true);

        // Verify that wpRegisterScript was called
        $this->assertTrue($wpService->wasCalled('wpRegisterScript'));

        // Verify that wpRegisterScript was called with the correct arguments
        $callLogItem = $wpService->getCallLog('wpRegisterScript');
        $this->assertContains(
            [
                'main.js',
                '/path/to/template/path/to/dist/main.js',
                ['jquery'],
                false,
                true,
            ],
            $callLogItem,
            'wpRegisterScript was not called with the expected arguments. Got:' . var_export($callLogItem, true)
        );
    }

    public function testWpRegisterStyleIsCalledWhenAddingStyleAndCarrysNormalizedParams()
    {
        $wpService = $this->getWpService();
        $manager = new EnqueueManager($wpService);
        $manager->setDistDirectory('/path/to/dist');

        $manager->add('main.css', ['bootstrap'], '1.0.0');

        // Verify that wpRegisterStyle was called
        $this->assertTrue($wpService->wasCalled('wpRegisterStyle'));

        // Verify that wpRegisterStyle was called with the correct arguments
        $callLogItem = $wpService->getCallLog('wpRegisterStyle');
        $this->assertContains(
            [
                'main.css',
                '/path/to/template/path/to/dist/main.css',
                ['bootstrap'],
                false
            ],
            $callLogItem,
            'wpRegisterStyle was not called with the expected arguments. Got:' . var_export($callLogItem, true)
        );
    }

    /**
     * Get a mock WordPress service for testing.
     *
     * @return FakeWpService
     */
    private function getWpService(): FakeWpService
    {
        return new FakeWpService(
            new HandlingFakeWpService()
        );
    }
}
