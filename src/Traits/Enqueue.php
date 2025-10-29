<?php

namespace WpUtilService\Traits;

use WpUtilService\Config\EnqueueManagerConfigInterface;
use WpUtilService\Features\EnqueueManager;
use WpUtilService\Features\CacheBustManager;

trait Enqueue
{
    use WpServiceTrait;

    /**
     * Entrypoint for the enqueue feature.
     *
     * Example usage:
     * $wpUtilService->enqueue(['distFolder' => '/var/www/dist'])
     *     ->add('main.js', ['jquery'], '1.0.0', true)
     *     ->addTranslation('main.js', 'my-textdomain')
     *     ->add('secondary.js');
     *
     * @param array $config Configuration options:
     *   - distFolder: string Path to asset distribution folder
     *   - manifestName: string Name of manifest file (default: 'manifest.json')
     *   - cacheBust: bool Enable cache busting (default: true)
     * @return \WpUtilService\Features\EnqueueManager Chainable manager for asset operations
     */
    public function enqueue(array $config = []): EnqueueManager
    {
        // Default config values
        $distDirectory = $config['distFolder'] ?? '/assets/dist/';
        $manifestName = $config['manifestName'] ?? 'manifest.json';
        $cacheBust = $config['cacheBust'] ?? true;

        // Setup config object
        $managerConfig = new \WpUtilService\Config\EnqueueManagerConfig();
        $managerConfig->setDistDirectory($distDirectory)
            ->setManifestName($manifestName)
            ->setCacheBustState($cacheBust);

        $cacheBustManager = null;
        if ($managerConfig->getIsCacheBustEnabled()) {
            $cacheBustManager = (new CacheBustManager($this->getWpService()))
                ->setManifestPath($managerConfig->getDistDirectory())
                ->setManifestName($managerConfig->getManifestName());
        }

        return (new EnqueueManager(
            $this->getWpService(),
            $cacheBustManager
        ))->setDistDirectory($managerConfig->getDistDirectory());
    }
}