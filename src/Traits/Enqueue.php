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
     * Example usage:
     *   $wpUtilService->enqueue(
     *       rootDirectory: '/var/www/project',
     *       distDirectory: '/assets/dist/',
     *       manifestName:  'manifest.json',
     *       cacheBust:     true
     *   )
     *   ->add('main.js', ['jquery'], '1.0.0', true)
     *   ->with()
     *       ->translation('objectName', [
     *           'localization_a' => ['Test']
     *       ])
     *   ->and()
     *       ->data('objectName', [
     *           'id' => 1
     *       ]);
     *
     * @param string $rootDirectory   Absolute path to project root directory, or any path within it. Required ONCE.
     * @param string $distDirectory   Path to asset distribution folder, relative to project root. Default: '/assets/dist/'.
     * @param string $manifestName    Name of manifest file. Default: 'manifest.json'.
     * @param bool   $cacheBust       Enable cache busting. Default: true.
     * @return \WpUtilService\Features\Enqueue\EnqueueManager Chainable manager for asset operations.
     */
    public function enqueue(
        ?string $rootDirectory = null,
        ?string $distDirectory = null,
        ?string $manifestName = null,
        bool $cacheBust = true
    ): EnqueueManager
    {
        //Config
        $enqueueManagerConfig = new \WpUtilService\Config\EnqueueManagerConfig();

        // Apply provided config overrides
        $rootDirectory   !== null ? $enqueueManagerConfig->setRootDirectory($rootDirectory): null;
        $distDirectory   !== null ? $enqueueManagerConfig->setDistDirectory($distDirectory): null;
        $manifestName    !== null ? $enqueueManagerConfig->setManifestName($manifestName): null;
        $cacheBust       !== null ? $enqueueManagerConfig->setCacheBustState($cacheBust): null;

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