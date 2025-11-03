<?php

namespace WpUtilService\Traits;

use WpUtilService\Config\EnqueueManagerConfigInterface;
use WpUtilService\Features\Enqueue\EnqueueManager;
use WpUtilService\Features\CacheBustManager;
use WpUtilService\Features\RuntimeContextManager;
use WpUtilService\WpServiceTrait;

trait Enqueue
{
    use WpServiceTrait;

    /**
     * Entrypoint for the enqueue feature.
     *
     *  $wpUtilService->enKo()
     * 
     * Example usage (en ko):
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
     *   - rootDirectory: string Path to project root directory, or any path within it. 
     *   - distDirectory: string Path to asset dist folder, path from project root. (default: '/assets/dist/')
     *   - manifestName: string Name of manifest file (default: 'manifest.json')
     *   - cacheBust: bool Enable cache busting (default: true)
     * @return \WpUtilService\Features\Enqueue\EnqueueManager Chainable manager for asset operations
     */
    public function enqueue(array $config = []): EnqueueManager
    {
        //Config
        $managerConfig = new \WpUtilService\Config\EnqueueManagerConfig();

        // Setup config object, if values are provided
        foreach ($config as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($managerConfig, $setter) && $value !== null) {
                $managerConfig->{$setter}($value);
            } else {
                throw new \InvalidArgumentException("Invalid configuration key '{$key}' for EnqueueManagerConfig.");
            }
        }

        //Setup runtime context
        $runtimeContext = (new RuntimeContextManager(
            $this->getWpService()
        ))->setPath($managerConfig->getRootDirectory());

        // Setup cache bust manager, if enabled
        $cacheBustManager = null;
        if ($managerConfig->getIsCacheBustEnabled()) {
            $cacheBustManager = new CacheBustManager($this->getWpService());
            
            $cacheBustManager->setManifestPath(
                $runtimeContext->getNormalizedRootPath() . $managerConfig->getDistDirectory()
            );

            $cacheBustManager->setManifestName(
                $managerConfig->getManifestName()
            );
        }

        //Return configured EnqueueManager
        return (new EnqueueManager(
            $this->getWpService(),
            $cacheBustManager
        ))->setDistDirectory($managerConfig->getDistDirectory());
    }
}