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
     */
    public function setDistDirectory(string $distDirectory): void
    {
        $this->assetsDistPath = rtrim($distDirectory, '/') . '/';
    }

    /**
     * Get the dist directory.
     */
    public function getDistDirectory(): null|string
    {
        return rtrim($this->assetsDistPath, '/') . '/';
    }

    /**
     * Resolve full asset URL (with cache-busting if available).
     */
    public function getAssetUrl(
        string $src,
        null|RuntimeContextEnum $contextMode = null,
        null|string $rootDirectory = null,
    ): string {
        $baseUrl = $this->resolveBaseUrl($contextMode, $rootDirectory);
        $assetName = $this->resolveAssetName($src);
        
        return $this->buildAssetUrl($baseUrl, $assetName);
    }

    /**
     * Resolve base URL based on context mode.
     */
    private function resolveBaseUrl(null|RuntimeContextEnum $contextMode, null|string $rootDirectory): string
    {
        return match ($contextMode) {
            RuntimeContextEnum::MUPLUGIN => $this->muPluginDirUrl($rootDirectory ?? ''),
            RuntimeContextEnum::PLUGIN => $this->wpService->pluginsUrl('', $rootDirectory . '/.'),
            RuntimeContextEnum::THEME => $this->wpService->getTemplateDirectoryUri(),
            null => $this->wpService->getSiteUrl(),
        };
    }

    /**
     * Resolve asset name with cache busting if enabled.
     */
    private function resolveAssetName(string $src): string
    {
        return $this->cacheBustManager ? $this->cacheBustManager->name($src) : $src;
    }

    /**
     * Build complete asset URL from parts.
     */
    private function buildAssetUrl(string $baseUrl, string $assetName): string
    {
        $parts = [$baseUrl, $this->assetsDistPath, $assetName];
        
        // Filter empty parts and trim slashes
        $parts = array_filter($parts, static fn($part) => !empty($part));
        $parts = array_map(static fn($part) => trim($part, '/'), $parts);
        
        return implode('/', $parts);
    }

    /**
     * MU-Plugin dir URL resolver.
     */
    private function muPluginDirUrl(string $rootDirectory): string
    {
        throw new \RuntimeException('MU-Plugin URL resolution not implemented yet.');
    }

    /**
     * Determine if a script is a module by checking manifest (if available).
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
