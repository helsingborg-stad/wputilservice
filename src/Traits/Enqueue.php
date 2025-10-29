<?php

namespace WpUtilService\Traits;

use WpUtilService\Config\EnqueueManagerConfigInterface;
use WpUtilService\Features\EnqueueManager;
use WpUtilService\Features\CacheBustManager;
use WpUtilService\WpServiceTrait;

trait Enqueue
{
    use WpServiceTrait;

    /**
     * Entrypoint for the enqueue feature.
     *
     * Example usage:
     * $wpUtilService->enqueue(['distFolder' => '/var/www/dist'])
     *     ->add('main.js', ['jquery'], '1.0.0', true)
     *         ->with()
     *             ->translation('objectName', [
     *                 'localization_a' => ['Test']
     *             ])
     *         ->and()
     *             ->data([
     *                 'id' => 1
     *             ]);
     *
     * @param array $config Configuration options:
     *   - distFolder: string Path to asset distribution folder
     *   - manifestName: string Name of manifest file (default: 'manifest.json')
     *   - cacheBust: bool Enable cache busting (default: true)
     * @return \WpUtilService\Features\EnqueueManager Chainable manager for asset operations
     */
    public function enqueue(array $config = []): EnqueueManager
    {
        //Config
        $managerConfig = new \WpUtilService\Config\EnqueueManagerConfig();

        // Default config values
        $distDirectory = $config['distFolder'] ?? null;
        $manifestName  = $config['manifestName'] ?? null;
        $cacheBust     = $config['cacheBust'] ?? null;

        // Setup config object, if values are provided
        $distDirectory !== null && $managerConfig->setDistDirectory($distDirectory);
        $manifestName  !== null && $managerConfig->setManifestName($manifestName);
        $cacheBust     !== null && $managerConfig->setCacheBustState($cacheBust);

        // Setup cache bust manager, if enabled
        $cacheBustManager = null;
        if ($managerConfig->getIsCacheBustEnabled()) {
            $cacheBustManager = (new CacheBustManager($this->getWpService()))
                ->setManifestPath($managerConfig->getDistDirectory())
                ->setManifestName($managerConfig->getManifestName());
        }

        //Return configured EnqueueManager
        return (new EnqueueManager(
            $this->getWpService(),
            $cacheBustManager
        ))->setDistDirectory($managerConfig->getDistDirectory());
    }
}