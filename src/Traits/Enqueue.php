<?php

namespace WpUtilService\Traits;

use WpUtilService\Config\EnqueueenqueueManagerConfigInterface;
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
     *   @type string $rootDirectory   Absolute path to project root directory, or any path within it. Required.
     *   @type string $distDirectory   Path to asset distribution folder, relative to project root. Default: '/assets/dist/'.
     *   @type string $manifestName    Name of manifest file. Default: 'manifest.json'.
     *   @type bool   $cacheBust       Enable cache busting. Default: true.
     * @return \WpUtilService\Features\Enqueue\EnqueueManager Chainable manager for asset operations
     */
    public function enqueue(array $config = []): EnqueueManager
    {
        //Config
        $enqueueManagerConfig = new \WpUtilService\Config\EnqueueManagerConfig();

        // Setup config object, if values are provided
        foreach ($config as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($enqueueManagerConfig, $setter) && $value !== null) {
                $enqueueManagerConfig->{$setter}($value);
            } else {
                throw new \InvalidArgumentException("Invalid configuration key '{$key}' for EnqueueManagerConfig.");
            }
        }

        //Setup runtime context
        $runtimeContext = (new RuntimeContextManager(
            $this->getWpService()
        ))->setPath($enqueueManagerConfig->getRootDirectory());

        // Setup cache bust manager, if enabled
        $cacheBustManager = null;
        if ($enqueueManagerConfig->getIsCacheBustEnabled()) {
            $cacheBustManager = new CacheBustManager($this->getWpService());
            
            $cacheBustManager->setManifestPath(
                $runtimeContext->getNormalizedRootPath() . $enqueueManagerConfig->getDistDirectory()
            );

            $cacheBustManager->setManifestName(
                $enqueueManagerConfig->getManifestName()
            );
        }

        //Return configured EnqueueManager
        return (new EnqueueManager(
            $this->getWpService(),
            $cacheBustManager
        ))->setDistDirectory($enqueueManagerConfig->getDistDirectory());
    }
}