<?php

namespace WpUtilService\Features;

use WpService\WpService;
use WpUtilService\WpServiceTrait;

/**
 * Class for managing cache busting of assets.
 */
class CacheBustManager
{
    use WpServiceTrait;

    /**
     * Storage var for the dist path and manifest name
     */
    private static string $manifestName  = 'manifest.json';
    private static ?string $manifestPath = '';

    /**
     * Set the manifest name.
     *
     * @param string $manifestName The name of the manifest file.
     */
    public function setManifestName(string $manifestName): self
    {
        if (empty($manifestName) || str_ends_with($manifestName, '.json') === false) {
            throw new \InvalidArgumentException("Manifest name cannot be empty and must end with .json");
        }
        self::$manifestName = $manifestName;
        return $this;
    }

    /**
     * Set the dist directory, and return the updated EnqueueManager instance.
     *
     * @param string $distDirectory The path to the distribution directory.
     */
    public function setManifestPath(string $manifestPath): self
    {
        self::$manifestPath = rtrim($manifestPath, '/') . '/';
        return $this;
    }

    /**
     * Get the full path to the manifest file.
     *
     * @return string The full path to the manifest file.
     */
    private function getManifestFilePath(): string
    {
        if (self::$manifestPath === null) {
            throw new \RuntimeException("Dist directory is not set. Please set it using setManifestPath() method.");
        }
        return self::$manifestPath . self::$manifestName;
    }

    /**
     * Return the manifest array from manifest.json file.
     * Supports MU-plugins, plugins, and themes.
     * Caches the manifest in a static variable and in WP object cache.
     */
    public function getManifest(): ?array
    {
        static $revManifest;

        $cacheKey = 'wputilservice-rev-manifest-' . md5($this->getManifestFilePath());

        if (!isset($revManifest)) {

            if ($this->getWpService()->wpCacheGet($cacheKey) !== false) {
                $revManifest = $this->getWpService()->wpCacheGet($cacheKey);
                return $revManifest;
            }

            $revManifestPath = $this->getManifestFilePath();
            if (file_exists($revManifestPath)) {
                $revManifest = json_decode(file_get_contents($revManifestPath), true);
                if (is_array($revManifest)) {
                    $this->getWpService()->wpCacheSet($cacheKey, $revManifest);
                    return $revManifest;
                }
            }

            throw new \RuntimeException("Failed to retrieve the manifest file.");
        }

        return $revManifest ?: null;
    }

    /**
     * Returns the revved/cache-busted file name of an asset.
     * @param string $name Asset name (array key) from rev-mainfest.json
     */
    public function name(string $name): string
    {
        $manifest = $this->getManifest();
        if (isset($manifest[$name])) {
            return $manifest[$name];
        }
        return $name;
    }
}