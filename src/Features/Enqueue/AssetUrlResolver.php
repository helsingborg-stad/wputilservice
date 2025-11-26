<?php

declare(strict_types=1);

namespace WpUtilService\Features\Enqueue;

use WpService\WpService;
use WpUtilService\Features\CacheBustManager;
use WpUtilService\Features\RuntimeContextEnum;

/**
 * Resolves asset URLs with optional cache busting.
 */
class AssetUrlResolver
{
    /**
     * @var string|null Storage var for the dist path
     */
    private null|string $assetsDistPath = null;

    /**
     * Constructor.
     *
     * @param WpService $wpService
     * @param CacheBustManager|null $cacheBustManager
     */
    public function __construct(
        private WpService $wpService,
        private null|CacheBustManager $cacheBustManager = null,
    ) {}

    /**
     * Set the dist directory.
     *
     * @param string $distDirectory Dist directory path
     */
    public function setDistDirectory(string $distDirectory): void
    {
        $this->assetsDistPath = rtrim($distDirectory, '/') . '/';
    }

    /**
     * Get the dist directory.
     *
     * @return string|null
     */
    public function getDistDirectory(): null|string
    {
        return rtrim($this->assetsDistPath ?? '', '/') . '/';
    }

    /**
     * Resolve full asset URL (with cache-busting if available).
     *
     * @param string $src Relative asset path
     * @param RuntimeContextEnum|null $contextMode Context mode for URL resolution
     * @param string|null $rootDirectory Root directory for plugin/MU-plugin contexts
     * @return string
     */
    public function getAssetUrl(
        string $src,
        null|RuntimeContextEnum $contextMode = null,
        null|string $rootDirectory = null,
    ): string {
        switch ($contextMode) {
            case RuntimeContextEnum::MUPLUGIN:
                $baseUrl = $this->muPluginDirUrl($src, $rootDirectory ?? '');
                break;
            case RuntimeContextEnum::PLUGIN:
                $baseUrl = $this->wpService->pluginsUrl('', $rootDirectory . '/.');
                break;
            case RuntimeContextEnum::THEME:
                $baseUrl = $this->wpService->getTemplateDirectoryUri();
                break;
            case null:
                $baseUrl = $this->wpService->getSiteUrl();
                break;
            default:
                throw new \InvalidArgumentException(
                    "Invalid context mode '{$contextMode->value}' for asset URL resolution.",
                );
        }

        $parts = [
            $baseUrl,
            $this->assetsDistPath,
            $this->cacheBustManager ? $this->cacheBustManager->name($src) : $src,
        ];

        // Trim both leading and trailing slashes from each part
        $parts = array_filter($parts, static fn($part) => !empty($part));
        $parts = array_map(static fn($part) => trim($part, '/'), $parts);
        $path = implode('/', $parts);

        // Remove debug output
        return $path;
    }

    /**
     * MU-Plugin dir URL resolver.
     *
     * @param string $src Relative asset path
     *
     * @param string|null $rootDirectory Root directory for MU-plugin context
     * @throws \RuntimeException
     * @return string
     */
    private function muPluginDirUrl(string $src, null|string $rootDirectory = null): string
    {
        throw new \RuntimeException('MU-Plugin URL resolution not implemented yet.');
        return '';
    }

    /**
     * Determine if a script is a module by checking manifest (if available).
     *
     * @param string $src Relative asset path
     * @return bool
     */
    public function isModule(string $src): bool
    {
        // Extract extension
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION) ?? '');

        if ($ext === 'js') {
            $manifest = $this->cacheBustManager ? $this->cacheBustManager->getManifest() : [];
            return isset($manifest[$src]);
        }

        return false;
    }
}
