<?php

declare(strict_types=1);

namespace WpUtilService\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WpUtilService\Features\RequestContextManager;

class RequestContextManagerTest extends TestCase
{
    #[DataProvider('assetLoadingRequestProvider')]
    /**
     * Determine whether a request can load assets.
     *
     * @param array<string, mixed> $server
     */
    public function testItDeterminesWhetherRequestCanLoadAssets(array $server, bool $expected): void
    {
        // Arrange
        $manager = new RequestContextManager();

        // Act
        $result = $manager->shouldEnqueueAssets($server);

        // Assert
        $this->assertSame($expected, $result);
    }

    /**
     * Provide normal page loads and non-rendering request contexts.
     *
     * @return array<string, array{array<string, mixed>, bool}>
     */
    public static function assetLoadingRequestProvider(): array
    {
        return [
            'normal page load' => [['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/about/'], true],
            'post save' => [['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/wp-admin/post.php'], false],
            'ajax endpoint' => [['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/wp-admin/admin-ajax.php'], false],
            'rest endpoint' => [['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/wp-json/wp/v2/posts'], false],
            'xml-rpc endpoint' => [['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/xmlrpc.php'], false],
        ];
    }
}