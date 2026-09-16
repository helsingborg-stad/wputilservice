<?php

declare(strict_types=1);

namespace WpUtilService\Features;

use WpService\WpService;

/**
 * Determines whether the current request can load frontend assets.
 */
class RequestContextManager
{
    /**
     * Create a request context manager.
     *
     * @param WpService $wpService WordPress service used to inspect executed actions.
     */
    public function __construct(
        private WpService $wpService,
    ) {}

    /**
     * Determine whether asset operations should run for the current request.
     *
     * @param array<string, mixed>|null $server Request server values. Defaults to $_SERVER.
     * @return bool True when the request is a page load that can render assets.
     */
    public function shouldEnqueueAssets(null|array $server = null): bool
    {
        $server ??= $_SERVER;
        $requestUri = (string) ($server['REQUEST_URI'] ?? '');

        if (defined('DOING_AJAX') && DOING_AJAX) {
            return false;
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            return false;
        }

        return !str_contains($requestUri, 'admin-ajax.php')
            && !str_contains($requestUri, 'xmlrpc.php')
            && !str_contains($requestUri, '/wp-json/');
    }
}
