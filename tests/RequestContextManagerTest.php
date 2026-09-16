<?php

declare(strict_types=1);

namespace WpUtilService\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WpUtilService\Features\RequestContextManager;
use WpUtilService\Tests\FakeWpService;

class RequestContextManagerTest extends TestCase
{
    #[DataProvider('assetLoadingRequestProvider')]
    /**
     * Determine whether a request can load assets.
     *
     * @param array<string, mixed> $server
     */
    public function testItDeterminesWhetherRequestCanLoadAssets(array $server, int $savePostActionCount, bool $expected): void
    {
        // Arrange
        $wpService = new FakeWpService(
            new HandlingFakeWpService(),
            ['didAction' => static fn(string $hookName): int => $hookName === 'save_post' ? $savePostActionCount : 0],
        );
        $manager = new RequestContextManager($wpService);

        // Act
        $result = $manager->shouldEnqueueAssets($server);

        // Assert
        $this->assertSame($expected, $result);
    }

    /**
     * Provide normal page loads and non-rendering request contexts.
     *
    * @return array<string, array{array<string, mixed>, int, bool}>
     */
    public static function assetLoadingRequestProvider(): array
    {
        return [
            'normal page load' => [['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/about/'], 0, true],
            'frontend form submission' => [['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/contact/'], 0, true],
            'save post action fired' => [['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/contact/'], 1, false],
            'post save' => [['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/wp-admin/post.php'], 0, false],
            'admin form handler' => [['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/wp-admin/admin-post.php'], 0, false],
            'ajax endpoint' => [['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/wp-admin/admin-ajax.php'], 0, false],
            'rest endpoint' => [['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/wp-json/wp/v2/posts'], 0, false],
            'xml-rpc endpoint' => [['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/xmlrpc.php'], 0, false],
        ];
    }
}