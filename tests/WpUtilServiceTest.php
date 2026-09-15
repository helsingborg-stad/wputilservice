<?php

declare(strict_types=1);

namespace WpUtilService\Tests;

use PHPUnit\Framework\TestCase;
use WpUtilService\Exceptions\EnqueueRootConflictException;
use WpUtilService\WpUtilService;

class WpUtilServiceTest extends TestCase
{
    public function testEnqueueConfigurationDoesNotLeakBetweenConsumers(): void
    {
        $firstService = new WpUtilService(new FakeWpService(new HandlingFakeWpService()));
        $firstService->enqueue(
            __DIR__ . '/fixtures/wp-content/themes/municipio',
            '/Modularity/assets/dist/',
        );

        $secondWpService = new FakeWpService(new HandlingFakeWpService());
        $secondService = new WpUtilService($secondWpService);
        $secondService
            ->enqueue(__DIR__ . '/fixtures/wp-content/mu-plugins/acf-openstreetmap-field')
            ->add('main.css');

        $registeredStyles = $secondWpService->getCallLog('wpRegisterStyle');
        $this->assertSame(
            'https://test.test/wp-content/mu-plugins/acf-openstreetmap-field/assets/dist/main.123.css',
            $registeredStyles[0][1] ?? null,
        );
    }

    public function testEnqueueConfigurationIsReusedByTheSameConsumer(): void
    {
        $wpService = new FakeWpService(new HandlingFakeWpService());
        $service = new WpUtilService($wpService);
        $service->enqueue(
            '/var/www/wp-content/mu-plugins/acf-openstreetmap-field',
            '/custom/dist/',
            cacheBust: false,
        );

        $service->enqueue(cacheBust: false)->add('main.css');

        $registeredStyles = $wpService->getCallLog('wpRegisterStyle');
        $this->assertSame(
            'https://test.test/wp-content/mu-plugins/acf-openstreetmap-field/custom/dist/main.css',
            $registeredStyles[0][1] ?? null,
        );
    }

    public function testSwitchingRootsOnTheSameServiceThrowsHelpfulException(): void
    {
        $wpService = new FakeWpService(new HandlingFakeWpService());
        $service = new WpUtilService($wpService);
        $service->enqueue(
            '/var/www/wp-content/themes/municipio',
            '/Modularity/assets/dist/',
            cacheBust: false,
        );

        try {
            $service->enqueue('/var/www/wp-content/mu-plugins/acf-openstreetmap-field', cacheBust: false);
            $this->fail('Expected a root conflict exception.');
        } catch (EnqueueRootConflictException $exception) {
            $this->assertStringContainsString(
                '/var/www/wp-content/themes/municipio/',
                $exception->getMessage(),
            );
            $this->assertStringContainsString(
                '/var/www/wp-content/mu-plugins/acf-openstreetmap-field/',
                $exception->getMessage(),
            );
            $this->assertStringContainsString('Create a separate WpUtilService instance', $exception->getMessage());
        }

        $service->enqueue(cacheBust: false)->add('main.css');

        $registeredStyles = $wpService->getCallLog('wpRegisterStyle');
        $this->assertSame('path/to/template/Modularity/assets/dist/main.css', $registeredStyles[0][1] ?? null);
    }

    public function testNestedPathsWithinTheSameRootDoNotConflict(): void
    {
        $wpService = new FakeWpService(new HandlingFakeWpService());
        $service = new WpUtilService($wpService);
        $service->enqueue(
            '/var/www/wp-content/mu-plugins/acf-openstreetmap-field',
            cacheBust: false,
        );

        $service
            ->enqueue(
                '/var/www/wp-content/mu-plugins/acf-openstreetmap-field/src/Field',
                cacheBust: false,
            )
            ->add('main.css');

        $registeredStyles = $wpService->getCallLog('wpRegisterStyle');
        $this->assertSame(
            'https://test.test/wp-content/mu-plugins/acf-openstreetmap-field/assets/dist/main.css',
            $registeredStyles[0][1] ?? null,
        );
    }
}
