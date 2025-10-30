<?php

declare(strict_types=1);

namespace WpUtilService\Features\Enqueue;

use WpService\WpService;
use WpUtilService\Features\CacheBustManager;

/**
 * Resolves asset URLs with optional cache busting.
 */
class AssetUrlResolver
{
    /**
     * @var string|null Storage var for the dist path
     */
    private static ?string $assetsDistPath = null;

    /**
     * Constructor.
     *
     * @param WpService $wpService
     * @param CacheBustManager|null $cacheBustManager
     */
    public function __construct(
        private WpService $wpService,
        private ?CacheBustManager $cacheBustManager = null
    ) {
    }

    /**
     * Set the dist directory.
     */
    public function setDistDirectory(string $distDirectory): void
    {
        self::$assetsDistPath = rtrim($distDirectory, '/') . '/';
    }

    /**
     * Get the dist directory.
     */
    public function getDistDirectory(): ?string
    {
        return self::$assetsDistPath;
    }

    /**
     * Resolve full asset URL (with cache-busting if available).
     */
    public function getAssetUrl(string $src): string
    {
        return $this->wpService->getTemplateDirectoryUri()
            . self::$assetsDistPath
            . ($this->cacheBustManager ? $this->cacheBustManager->name($src) : $src);
    }

    /**
     * Determine if a script is a module by checking manifest (if available).
     */
    public function isModule(string $src): bool
    {
        if (!str_ends_with($src, '.js')) {
            return false;
        }

        $manifest = $this->cacheBustManager ? $this->cacheBustManager->getManifest() : [];
        return isset($manifest[$src]);
    }
}
