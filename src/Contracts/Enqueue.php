<?php

namespace WpUtilService\Contracts;

use WpUtilService\Features\Enqueue\EnqueueManager;

/**
 * Interface for enqueue feature contract.
 */
interface Enqueue
{
    /**
     * Returns an EnqueueManager instance for managing asset enqueuing.
     *
     * @param string $rootDirectory   Absolute path to project root directory, or any path within it. Required.
     * @param string $distDirectory   Path to asset distribution folder, relative to project root. Default: '/assets/dist/'.
     * @param string $manifestName    Name of manifest file. Default: 'manifest.json'.
     * @param bool   $cacheBust       Enable cache busting. Default: true.
     * @return EnqueueManager
     */
    public function enqueue(
        ?string $rootDirectory = null,
        ?string $distDirectory = null,
        ?string $manifestName = null,
        bool $cacheBust = true
    ): EnqueueManager;
}
