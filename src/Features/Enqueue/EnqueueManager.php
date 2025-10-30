<?php

declare(strict_types=1);

namespace WpUtilService\Features\Enqueue;

use WpService\WpService;
use WpUtilService\Contracts\Enqueue;
use WpUtilService\Features\CacheBustManager;

/**
 * Manager for enqueuing assets with fluent API and context chaining.
 */
class EnqueueManager implements Enqueue
{
    /**
     * @var array Configuration options
     */
    private array $config = [];

    /**
     * @var string|null Last added handle name
     */
    private ?string $lastHandle = null;

    /**
     * @var array Storage for handles that have seen the with() function
     */
    private array $handleHasSeenWithFunction = [];

    /**
     * @var AssetUrlResolver
     */
    private AssetUrlResolver $assetUrlResolver;

    /**
     * @var AssetRegistrar
     */
    private AssetRegistrar $assetRegistrar;

    /**
     * @var AssetLocalization
     */
    private AssetLocalization $assetLocalization;

    /**
     * @var ScriptAttributeManager
     */
    private ScriptAttributeManager $scriptAttributeManager;

    /**
     * Constructor.
     *
     * @param WpService $wpService
     * @param CacheBustManager|null $cacheBustManager
     * @param array $config
     */
    public function __construct(
        private WpService $wpService,
        private ?CacheBustManager $cacheBustManager = null,
        array $config = []
    ) {
        $this->config = $config;
        
        // Initialize support classes
        $this->assetUrlResolver = new AssetUrlResolver($wpService, $cacheBustManager);
        $this->assetRegistrar = new AssetRegistrar($wpService);
        $this->assetLocalization = new AssetLocalization($this->assetRegistrar);
        $this->scriptAttributeManager = new ScriptAttributeManager($wpService);
    }

    /**
     * Get the instance of the EnqueueManager.
     */
    public function enqueue(): EnqueueManager
    {
        return $this;
    }

    /**
     * Set the dist directory and return this instance (fluent).
     */
    public function setDistDirectory(string $distDirectory): self
    {
        $this->assetUrlResolver->setDistDirectory($distDirectory);
        $this->config['distFolder'] = $this->assetUrlResolver->getDistDirectory();

        return $this;
    }

    /**
     * Chainable method to add a script or style asset.
     */
    public function add(string $src, array $deps = [], ?string $version = null, ?bool $module = null): self
    {
        $handle = pathinfo($src, PATHINFO_FILENAME);

        // Ensure the handle includes a valid file extension
        if (!str_ends_with($handle, '.css') && !str_ends_with($handle, '.js')) {
            $extension = pathinfo($src, PATHINFO_EXTENSION);
            $handle   .= $extension ? ".{$extension}" : '';
        }

        $this->addAsset($handle, $src, $deps, $module);
        $this->lastHandle = $handle;
        return $this;
    }

    /**
     * Returns a context object for the last added asset, enabling .with()->... chaining.
     *
     * @param string|null $function Optional specific method to call on the context object (shortcut).
     * 
     * @throws \RuntimeException
     */
    public function with(?string $function = null, ...$args): EnqueueManager|EnqueueAssetContext
    {
        if (!$this->lastHandle) {
            throw new \RuntimeException('No asset has been added to attach context.');
        }
        $this->handleHasSeenWithFunction[$this->lastHandle] = true;

        $assetContext = new EnqueueAssetContext($this, $this->lastHandle);

        if (!is_null($function) && method_exists($assetContext, $function)) {
            $assetContext->$function(...$args);
            return $this; // Return the manager instance after executing the function
        }

        return $assetContext; // Return the context instance if no function is executed
    }

    /**
     * Alias for chaining convenience — requires with() to have been called first.
     *
     * @param string|null $function Optional specific method to call on the context object (shortcut).
     *
     * @throws \RuntimeException
     */
    public function and(?string $function = null, ...$args): EnqueueAssetContext
    {
        if ($this->lastHandle === null || !isset($this->handleHasSeenWithFunction[$this->lastHandle])) {
            throw new \RuntimeException(
                'Chaining and() is not allowed before with(). Looking for: '
                . (string) $this->lastHandle
                . ' In dataset: ' . json_encode($this->handleHasSeenWithFunction)
            );
        }
        $this->with($function, ...$args);
        
        return $this->with();
    }

    /**
     * Attach translation data to a specific asset handle.
     *
     * @param string $handle
     * @param string $objectName
     * @param array $localizationData
     *
     * @throws \RuntimeException|\InvalidArgumentException
     */
    public function addTranslationToHandle(string $handle, string $objectName, array $localizationData): void
    {
        $this->assetLocalization->addTranslationToHandle($handle, $objectName, $localizationData);
    }

    /**
     * Attach arbitrary data to a specific asset handle (for extensibility).
     *
     * @param string $handle
     * @param array $data
     *
     * @throws \RuntimeException
     */
    public function addDataToHandle(string $handle, array $data): void
    {
        $this->assetLocalization->addDataToHandle($handle, $data);
    }

    /**
     * Chainable convenience: addTranslation by source (file) and textdomain.
     *
     * @param string $src
     * @param string $textDomain
     * @return self
     *
     * @throws \RuntimeException
     */
    public function addTranslation(string $src, string $textDomain): self
    {
        $handle = pathinfo($src, PATHINFO_FILENAME);
        $funcs  = $this->assetRegistrar->getRegisterEnqueueFunctions(
            $this->assetRegistrar->getFileType($src, $handle)
        );

        if (isset($funcs['localize'])) {
            $funcs['localize']($handle, $textDomain, []);
        } else {
            throw new \RuntimeException('Localization is not supported for this asset type.');
        }

        return $this;
    }

    /**
     * Adds an asset (CSS/JS) to the queue of assets to be rendered.
     *
     * @param string $handle
     * @param string $src
     * @param array $deps
     * @param bool|null $module
     *
     * @throws \InvalidArgumentException|\RuntimeException
     */
    public function addAsset(string $handle, string $src, array $deps = [], ?bool $module = null): void
    {
        $this->validateAddAssetParams($handle, $src);

        $fileType = $this->assetRegistrar->getFileType($src, $handle);
        $func = $this->assetRegistrar->getRegisterEnqueueFunctions($fileType);

        $module  = $module ?? $this->assetUrlResolver->isModule($src);
        $fullSrc = $this->assetUrlResolver->getAssetUrl($src);

        $func['register']($handle, $fullSrc, $deps);

        if ($module === true) {
            $this->scriptAttributeManager->addAttributesToScriptTag($handle, ['type' => 'module']);
        }

        $func['enqueue']($handle);
    }

    /**
     * Validate parameters for addAsset.
     *
     * @throws \InvalidArgumentException|\RuntimeException
     */
    private function validateAddAssetParams(string $handle, string $src): void
    {
        if (empty($handle)) {
            throw new \InvalidArgumentException('Handle cannot be empty.');
        }

        if (empty($src)) {
            throw new \InvalidArgumentException('Source cannot be empty.');
        }

        if ($this->assetUrlResolver->getDistDirectory() === null) {
            throw new \RuntimeException('Dist directory is not set. Please set it using setDistDirectory() method.');
        }
    }
}
