<?php

declare(strict_types=1);

namespace WpUtilService\Features\Enqueue;

use WpService\WpService;

/**
 * Manages script tag attributes (e.g., type="module").
 */
class ScriptAttributeManager
{
    /**
     * Constructor.
     *
     * @param WpService $wpService
     */
    public function __construct(
        private WpService $wpService
    ) {
    }

    /**
     * Add attributes to script tag for given handle.
     *
     * Uses script_loader_tag filter to inject attributes like type="module".
     */
    public function addAttributesToScriptTag(string $handle, array $attributes): void
    {
        $this->wpService->addFilter(
            'script_loader_tag',
            function ($tag, $tagHandle) use ($handle, $attributes) {
                if ($tagHandle === $handle) {
                    foreach ($attributes as $key => $value) {
                        // Insert attribute before src=
                        $tag = str_replace(
                            ' src=',
                            sprintf(' %s="%s" src=', esc_attr($key), esc_attr($value)),
                            $tag
                        );
                    }
                }
                return $tag;
            },
            10,
            2
        );
    }
}
