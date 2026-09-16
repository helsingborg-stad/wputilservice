<?php
declare(strict_types=1);

namespace WpUtilService\Traits;

use WpUtilService\Config\EnqueueManagerConfig;
use WpUtilService\Features\CacheBustManager;
use WpUtilService\Features\Enqueue\EnqueueManager;
use WpUtilService\Features\RuntimeContextManager;
use WpUtilService\WpServiceTrait;

trait Enqueue
{
    use WpServiceTrait;

    /** @var array<string, EnqueueManagerConfig> */
    private array $enqueueManagerConfigs = [];
    private null|string $activeEnqueueRootPath = null;

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
     *                                first call. Configuration is kept separately for each theme or plugin root.
     * @param string $distDirectory   Path to asset distribution folder, relative to project root. Default: '/assets/dist/'.
     * @param string $manifestName    Name of manifest file. Default: 'manifest.json'.
     * @param bool   $cacheBust       Enable cache busting. Default: true.
     * @return \WpUtilService\Features\Enqueue\EnqueueManager Chainable manager for asset operations.
     */
    public function enqueue(
        null|string $rootDirectory = null,
        null|string $distDirectory = null,
        null|string $manifestName = null,
        bool $cacheBust = true,
    ): EnqueueManager {
        if ($rootDirectory !== null) {
            $requestedRootPath = (string) (new RuntimeContextManager())
                ->setPath($rootDirectory)
                ->getNormalizedRootPath();
            $this->activeEnqueueRootPath = $requestedRootPath;
            $enqueueManagerConfig = $this->enqueueManagerConfigs[$requestedRootPath]
                ??= new EnqueueManagerConfig();
        } elseif ($this->activeEnqueueRootPath !== null) {
            $enqueueManagerConfig = $this->enqueueManagerConfigs[$this->activeEnqueueRootPath];
        } else {
            $enqueueManagerConfig = new EnqueueManagerConfig();
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
        $rootPath = $this->activeEnqueueRootPath ?? $runtimeContext->getNormalizedRootPath();

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
