<?php

declare(strict_types=1);

namespace WpUtilService\Features;

use WpService\WpService;
use WpUtilService\Contracts\Enqueue;

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
     * @var array Track used translation object names to enforce uniqueness
     */
    private array $usedTranslationObjectNames = [];

    /**
     * @var string|null Storage var for the dist path
     */
    private static ?string $assetsDistPath = null;

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
    }

    /**
     * Get the instance of the EnqueueManager.
     */
    public function getEnqueueManager(): EnqueueManager
    {
        return $this;
    }

    /**
     * Set the dist directory and return this instance (fluent).
     */
    public function setDistDirectory(string $distDirectory): self
    {
        self::$assetsDistPath       = rtrim($distDirectory, '/') . '/';
        $this->config['distFolder'] = self::$assetsDistPath;

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
     * @throws \RuntimeException
     */
    public function with(): EnqueueAssetContext
    {
        if (!$this->lastHandle) {
            throw new \RuntimeException('No asset has been added to attach context.');
        }
        $this->handleHasSeenWithFunction[$this->lastHandle] = true;
        return new EnqueueAssetContext($this, $this->lastHandle);
    }

    /**
     * Alias for chaining convenience — requires with() to have been called first.
     *
     * @throws \RuntimeException
     */
    public function and(): EnqueueAssetContext
    {
        if ($this->lastHandle === null || !isset($this->handleHasSeenWithFunction[$this->lastHandle])) {
            throw new \RuntimeException(
                'Chaining and() is not allowed before with(). Looking for: '
                . (string) $this->lastHandle
                . ' In dataset: ' . json_encode($this->handleHasSeenWithFunction)
            );
        }
        return $this->with();
    }

    /**
     * Attach translation data to a specific asset handle.
     *
     * Enforces:
     *  - only JS assets accept translations (wp_localize_script)
     *  - translation object names must be unique across all assets
     *
     * @param string $handle
     * @param string $objectName
     * @param array $localizationData associative array to pass to wp_localize_script
     *
     * @throws \RuntimeException|\InvalidArgumentException
     */
    public function addTranslationToHandle(string $handle, string $objectName, array $localizationData): void
    {
        $assetType = $this->getAssetTypeForHandle($handle);

        if ($assetType === 'css') {
            throw new \RuntimeException('Cannot add translation to a CSS asset.');
        }

        if (in_array($objectName, $this->usedTranslationObjectNames, true)) {
            throw new \RuntimeException("Translation object name '{$objectName}' must be unique across all assets.");
        }

        $this->usedTranslationObjectNames[] = $objectName;

        $funcs = $this->getRegisterEnqeueFunctions('js');

        if (isset($funcs['localize'])) {
            if (!is_array($localizationData)) {
                throw new \InvalidArgumentException('Localization data must be an array.');
            }
            // Pass full data through wp_localize_script as-is
            $funcs['localize']($handle, $objectName, $localizationData);
        } else {
            throw new \RuntimeException('Localization is not supported for this asset type.');
        }
    }

    /**
     * Attach arbitrary data to a specific asset handle (for extensibility).
     *
     * Currently only allowed for JS assets (placeholder for future features).
     *
     * @param string $handle
     * @param array $data
     *
     * @throws \RuntimeException
     */
    public function addDataToHandle(string $handle, array $data): void
    {
        $assetType = $this->getAssetTypeForHandle($handle);
        if ($assetType === 'css') {
            throw new \RuntimeException('Cannot add data to a CSS asset.');
        }

        // Placeholder: store or process data for the asset (e.g. inline JSON, custom attributes)
        // e.g. $this->inlineData[$handle] = $data; — implement storage as needed.
    }

    /**
     * Infer the asset type for a given handle.
     *
     * Best-effort: tries to infer from lastHandle and config; otherwise defaults to 'js'.
     *
     * @param string $handle
     * @return string 'js'|'css'
     */
    private function getAssetTypeForHandle(string $handle): string
    {
        // Directly check the suffix of the handle
        if (str_ends_with($handle, '.css')) {
            return 'css';
        }
        if (str_ends_with($handle, '.js')) {
            return 'js';
        }

        // Throw an exception if the type cannot be determined
        throw new \InvalidArgumentException("Cannot determine asset type for handle: {$handle}");
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
        $funcs  = $this->getRegisterEnqeueFunctions($this->getFileType($src, $handle));

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

        $func = $this->getRegisterEnqeueFunctions(
            $this->getFileType($src, $handle)
        );

        $module                     = $module ?? $this->isModule($src, $handle);
        $fullSrc = $this->getAssetUrl($src);

        $func['register']($handle, $fullSrc, $deps);

        if ($module === true) {
            $this->addAttributesToScriptTag($handle, ['type' => 'module']);
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

        if (self::$assetsDistPath === null) {
            throw new \RuntimeException('Dist directory is not set. Please set it using setDistDirectory() method.');
        }
    }

    /**
     * Get the register/enqueue/localize callables for a type.
     *
     * @param string $type 'js'|'css'
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    private function getRegisterEnqeueFunctions(string $type): array
    {
        if ($type === 'js') {
            return [
                'register' => fn($handle, $src, $deps) =>
                    $this->wpService->wpRegisterScript($handle, $src, $deps, false, true),
                'enqueue'  => fn($handle) =>
                    $this->wpService->wpEnqueueScript($handle),
                'localize' => fn($handle, $objectName, $data) =>
                    $this->wpService->wpLocalizeScript($handle, $objectName, $data),
            ];
        }

        if ($type === 'css') {
            return [
                'register' => fn($handle, $src, $deps) =>
                    $this->wpService->wpRegisterStyle($handle, $src, $deps, false),
                'enqueue'  => fn($handle) =>
                    $this->wpService->wpEnqueueStyle($handle),
            ];
        }

        throw new \InvalidArgumentException('Invalid type provided. Use "js" or "css".');
    }

    /**
     * Add attributes to script tag for given handle.
     *
     * Uses script_loader_tag filter to inject attributes like type="module".
     */
    private function addAttributesToScriptTag(string $handle, array $attributes): void
    {
        $this->wpService->addFilter(
            'script_loader_tag',
            function ($tag, $tagHandle) use ($handle, $attributes) {
                if ($tagHandle === $handle) {
                    foreach ($attributes as $key => $value) {
                        // Insert attribute before src=
                        $tag = str_replace(
                            ' src=',
                            sprintf(' %s="%s" src=', esc_attr($key), esc_attr($value)),
                            $tag
                        );
                    }
                }
                return $tag;
            },
            10,
            2
        );
    }

    /**
     * Get the file type from the source string.
     *
     * @param string $src
     * @param string $handle
     * @return string 'js'|'css'
     *
     * @throws \InvalidArgumentException
     */
    private function getFileType(string $src, string $handle = ''): string
    {
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION) ?? '');

        if (empty($ext)) {
            throw new \InvalidArgumentException(
                "Could not determine file extension from source: {$src} using handle: {$handle}"
            );
        }

        if (!in_array($ext, ['js', 'css'], true)) {
            throw new \InvalidArgumentException("Unsupported file extension: {$ext}");
        }

        return $ext;
    }

    /**
     * Resolve full asset URL (with cache-busting if available).
     */
    private function getAssetUrl(string $src): string
    {
        return $this->wpService->getTemplateDirectoryUri()
            . self::$assetsDistPath
            . ($this->cacheBustManager ? $this->cacheBustManager->name($src) : $src);
    }

    /**
     * Determine if a script is a module by checking manifest (if available).
     */
    private function isModule(string $src, string $handle = ''): bool
    {
        $ext = $this->getFileType($src, $handle);

        if ($ext === 'js') {
            $manifest = $this->cacheBustManager ? $this->cacheBustManager->getManifest() : [];
            return isset($manifest[$src]);
        }

        return false;
    }
}
