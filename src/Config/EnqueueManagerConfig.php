<?php

namespace WpUtilService\Config;

use WpUtilService\Config\EnqueueManagerConfigInterface as I;

/**
 * Implementation of EnqueueManagerConfigInterface for asset configuration.
 */
class EnqueueManagerConfig implements I
{
    protected static bool $cacheBust       = true;
    protected static string $distDirectory = '/assets/dist/';
    protected static string $manifestName  = 'manifest.json';
    protected static ?string $rootDirectory  = null;

    /**
     * Set cache busting state.
     */
    public function setCacheBustState(bool $cacheBust): I
    {
        self::$cacheBust = $cacheBust;
        return $this;
    }

    public function setRootDirectory(string $rootDirectory): I
    {
        self::$rootDirectory = $rootDirectory;
        return $this;
    }

    /**
     * Set distribution directory.
     */
    public function setDistDirectory(string $distDirectory): I
    {
        self::$distDirectory = $distDirectory;
        return $this;
    }

    /**
     * Set manifest name.
     */
    public function setManifestName(string $manifestName): I
    {
        self::$manifestName = $manifestName;
        return $this;
    }

    /**
     * Get cache busting enabled state.
     */
    public function getIsCacheBustEnabled(): bool
    {
        return self::$cacheBust;
    }

    public function getRootDirectory(): ?string
    {
        return self::$rootDirectory;
    }

    /**
     * Get distribution directory.
     */
    public function getDistDirectory(): string
    {
        return self::$distDirectory;
    }

    /**
     * Get manifest name.
     */
    public function getManifestName(): string
    {
        return self::$manifestName;
    }
}