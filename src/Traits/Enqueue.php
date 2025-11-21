<?php
declare(strict_types=1);

namespace WpUtilService\Traits;

use WpUtilService\Features\CacheBustManager;
use WpUtilService\Features\Enqueue\EnqueueManager;
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
        null|string $rootDirectory = null,
        null|string $distDirectory = null,
        null|string $manifestName = null,
        bool $cacheBust = true,
    ): EnqueueManager {
        $config = $this->buildEnqueueConfig($rootDirectory, $distDirectory, $manifestName, $cacheBust);
        $runtimeContext = $this->createRuntimeContext($config->getRootDirectory());
        $cacheBustManager = $this->createCacheBustManager($config, $runtimeContext);

        return (new EnqueueManager($this->getWpService(), $cacheBustManager))
            ->setDistDirectory($config->getDistDirectory())
            ->setContextMode($runtimeContext->getContextOfPath())
            ->setRootDirectory($config->getRootDirectory());
    }

    /**
     * Build configuration for EnqueueManager.
     */
    private function buildEnqueueConfig(
        null|string $rootDirectory,
        null|string $distDirectory,
        null|string $manifestName,
        bool $cacheBust
    ): \WpUtilService\Config\EnqueueManagerConfig {
        $config = new \WpUtilService\Config\EnqueueManagerConfig();

        if ($rootDirectory !== null) {
            $config->setRootDirectory($rootDirectory);
        }
        if ($distDirectory !== null) {
            $config->setDistDirectory($distDirectory);
        }
        if ($manifestName !== null) {
            $config->setManifestName($manifestName);
        }
        $config->setCacheBustState($cacheBust);

        return $config;
    }

    /**
     * Create runtime context from root directory.
     */
    private function createRuntimeContext(string $rootDirectory): RuntimeContextManager
    {
        return (new RuntimeContextManager())->setPath($rootDirectory);
    }

    /**
     * Create cache bust manager if enabled.
     */
    private function createCacheBustManager(
        \WpUtilService\Config\EnqueueManagerConfig $config,
        RuntimeContextManager $runtimeContext
    ): null|CacheBustManager {
        if (!$config->getIsCacheBustEnabled()) {
            return null;
        }

        $cacheBustManager = new CacheBustManager($this->getWpService());
        $cacheBustManager->setManifestPath(
            $runtimeContext->getNormalizedRootPath() . $config->getDistDirectory()
        );
        $cacheBustManager->setManifestName($config->getManifestName());

        return $cacheBustManager;
    }
}
