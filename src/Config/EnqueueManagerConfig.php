<?php

namespace WpUtilService\Config;

use WpUtilService\Config\EnqueueManagerConfigInterface as I;

<?php
declare(strict_types=1);

/**
 * Implementation of EnqueueManagerConfigInterface for asset configuration.
 */
class EnqueueManagerConfig implements I
{
    protected bool $cacheBust = true;
    protected string $distDirectory = '/assets/dist/';
    protected string $manifestName = 'manifest.json';

    /**
     * Set cache busting state.
     */
    public function setCacheBustState(bool $cacheBust): I
    {
        $this->cacheBust = $cacheBust;
        return $this;
    }

    /**
     * Set distribution directory.
     */
    public function setDistDirectory(string $distDirectory): I
    {
        $this->distDirectory = $distDirectory;
        return $this;
    }

    /**
     * Set manifest name.
     */
    public function setManifestName(string $manifestName): I
    {
        $this->manifestName = $manifestName;
        return $this;
    }

    /**
     * Get cache busting enabled state.
     */
    public function getIsCacheBustEnabled(): bool
    {
        return $this->cacheBust;
    }

    /**
     * Get distribution directory.
     */
    public function getDistDirectory(): string
    {
        return $this->distDirectory;
    }

    /**
     * Get manifest name.
     */
    public function getManifestName(): string
    {
        return $this->manifestName;
    }
}