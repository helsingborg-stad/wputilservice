<?php

declare(strict_types=1);

namespace WpUtilService\Features;

/**
 * Determines whether the current request can load frontend assets.
 */
class RequestContextManager
{
    /**
     * Determine whether asset operations should run for the current request.
     *
     * @param array<string, mixed>|null $server Request server values. Defaults to $_SERVER.
     * @return bool True when the request is a page load that can render assets.
     */
    public function shouldEnqueueAssets(null|array $server = null): bool
    {
        $server ??= $_SERVER;
        $requestMethod = strtoupper((string) ($server['REQUEST_METHOD'] ?? ''));
        $requestUri = (string) ($server['REQUEST_URI'] ?? '');

        if ($requestMethod !== '' && !in_array($requestMethod, ['GET', 'HEAD'], true)) {
            return false;
        }

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