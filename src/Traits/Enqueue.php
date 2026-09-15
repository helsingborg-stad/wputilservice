<?php
declare(strict_types=1);

namespace WpUtilService\Traits;

use WpUtilService\Config\EnqueueManagerConfig;
use WpUtilService\Exceptions\EnqueueRootConflictException;
use WpUtilService\Features\CacheBustManager;
use WpUtilService\Features\Enqueue\EnqueueManager;
use WpUtilService\Features\RuntimeContextManager;
use WpUtilService\WpServiceTrait;

trait Enqueue
{
    use WpServiceTrait;

    private null|EnqueueManagerConfig $enqueueManagerConfig = null;
    private null|string $enqueueRootPath = null;

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
     *   ->on('wp_enqueue_scripts', 20)
     *   ->with()
     *       ->translation('objectName', [
     *           'localization_a' => ['Test']
     *       ])
     *   ->and()
     *       ->data('objectName', [
     *           'id' => 1
     *       ]);
     *
     * @param string $rootDirectory   Absolute path to project root directory, or any path within it. Required on the
     *                                first call for each WpUtilService instance.
     * @param string $distDirectory   Path to asset distribution folder, relative to project root. Default: '/assets/dist/'.
     * @param string $manifestName    Name of manifest file. Default: 'manifest.json'.
     * @param bool   $cacheBust       Enable cache busting. Default: true.
     * @return \WpUtilService\Features\Enqueue\EnqueueManager Chainable manager for asset operations.
     * @throws EnqueueRootConflictException When the service is already configured for another theme or plugin.
     */
    public function enqueue(
        null|string $rootDirectory = null,
        null|string $distDirectory = null,
        null|string $manifestName = null,
        bool $cacheBust = true,
    ): EnqueueManager {
        $enqueueManagerConfig = $this->enqueueManagerConfig ??= new EnqueueManagerConfig();

        if ($rootDirectory !== null) {
            $requestedRootPath = (string) (new RuntimeContextManager())
                ->setPath($rootDirectory)
                ->getNormalizedRootPath();

            if ($this->enqueueRootPath !== null && $requestedRootPath !== $this->enqueueRootPath) {
                throw new EnqueueRootConflictException($this->enqueueRootPath, $requestedRootPath);
            }

            $this->enqueueRootPath = $requestedRootPath;
        }

        // Apply provided config overrides
        if ($rootDirectory !== null) {
            $enqueueManagerConfig->setRootDirectory($rootDirectory);
        }
        if ($distDirectory !== null) {
            $enqueueManagerConfig->setDistDirectory($distDirectory);
        }
        if ($manifestName !== null) {
            $enqueueManagerConfig->setManifestName($manifestName);
        }
        $enqueueManagerConfig->setCacheBustState($cacheBust);

        //Setup runtime context
        $runtimeContext = (new RuntimeContextManager())->setPath($enqueueManagerConfig->getRootDirectory());
        $rootPath = $this->enqueueRootPath ??= $runtimeContext->getNormalizedRootPath();

        // Setup cache bust manager, if enabled
        $cacheBustManager = null;
        if ($enqueueManagerConfig->getIsCacheBustEnabled()) {
            $cacheBustManager = new CacheBustManager($this->getWpService());

            $cacheBustManager->setManifestPath(
                rtrim($rootPath, '/') . '/' . trim($enqueueManagerConfig->getDistDirectory(), '/'),
            );

            $cacheBustManager->setManifestName($enqueueManagerConfig->getManifestName());
        }

        //Return configured EnqueueManager
        return (new EnqueueManager($this->getWpService(), $cacheBustManager))
            ->setDistDirectory($enqueueManagerConfig->getDistDirectory())
            ->setContextMode($runtimeContext->getContextOfPath())
            ->setRootDirectory($rootPath);
    }
}
