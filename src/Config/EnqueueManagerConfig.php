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
    protected static ?array $hooks = null;

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
     * Set hooks with their priorities.
     */
    public function setHooks(array $hooks): I
    {
        //Validate 'string' => int pairs
        foreach($hooks as $hookName => $priority) {
            if(!is_string($hookName) || !is_int($priority)) {
                throw new \InvalidArgumentException("Hooks must be an array of 'string' => int pairs.");
            }
        }
        self::$hooks = $hooks;
        return $this;
    }

    /**
     * Get cache busting enabled state.
     */
    public function getIsCacheBustEnabled(): bool
    {
        return self::$cacheBust;
    }

    /**
     * Get root directory.
     */
    public function getRootDirectory(): ?string
    {
        if(is_null(self::$rootDirectory)) {
            throw new \RuntimeException("Root directory is not set. Please provide it using setRootDirectory() method.");
        }
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

    /**
     * Get hooks with their priorities.
     */
    public function getHooks(): ?array
    {
        return self::$hooks;
    }
}