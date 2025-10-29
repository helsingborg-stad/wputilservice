<?php

declare(strict_types=1);

namespace WpUtilService\Features;

use WpService\WpService;
use WpUtilService\Contracts\Enqueue;

/**
 * Manager for enqueuing assets with fluent API and context chaining.
 */
class EnqueueManager
{
    /**
     * @var string|null Storage variable for the last added asset handle
     */
    private ?string $lastHandle = null;

    /**
     * @var array Storage for handles that have seen the with() function
     */
    private array $handleHasSeenWithFunction = [];

    /**
     * @var array Configuration options
     */
    private array $config = [];

    /**
    private ?string $lastHandle = null;

    /**
     * @var string|null Storage variable for the dist path
     */
    private static ?string $assetsDistPath = null;

    /**
     * Constructor for EnqueueManager.
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
     * Set the dist directory and return the updated EnqueueManager instance.
     *
     * @param string $distDirectory The path to the distribution directory.
     * @return self
     */
    public function setDistDirectory(string $distDirectory): self
    {
        self::$assetsDistPath = rtrim($distDirectory, '/') . '/';
        $this->config['distFolder'] = self::$assetsDistPath;
        return $this;
    }

    /**
     * Chainable method to add a script or style asset.
     *
     * @param string $src Asset source file name.
     * @param array $deps Dependencies.
     * @param string|null $version Asset version.
     * @param bool|null $module JS module flag.
     * @return self
     */
    public function add(string $src, array $deps = [], ?string $version = null, ?bool $module = null): self
    {
        $handle = pathinfo($src, PATHINFO_FILENAME);
        $this->addAsset($handle, $src, $deps, $module);
        $this->lastHandle = $handle;
        return $this;
    }

    /**
     * Returns a context object for the last added asset,
     * enabling .with()->translation() / .data() chaining.
     *
     * @return EnqueueAssetContext
     */
    public function with(): EnqueueAssetContext
    {
        if (!$this->lastHandle) {
            throw new \RuntimeException('No asset has been added to attach context.');
        }
        $this->handleHasSeenWithFunction[$this->lastHandle] = true; //Allow and chaining
        return new EnqueueAssetContext($this, $this->lastHandle);
    }

    /**
     * This is a alias for with, enabling chaining to be
     * done in a more natural language.
     *
     * @return EnqueueAssetContext
     */
    public function and(): EnqueueAssetContext
    {
        if (!isset($this->handleHasSeenWithFunction[$this->lastHandle])) {
            throw new \RuntimeException('Chaining and() is not allowed before with(). Looking for: ' . $this->lastHandle . ' In dataset: ' . json_encode($this->handleHasSeenWithFunction));
        }
        return $this->with();
    }

    /**
     * Attach translation data to a specific asset handle.
     *
     * @param string $handle
     * @param array $localizationData
     * @return void
     */
    public function addTranslationToHandle(string $handle, string $objectName, array $localizationData): void
    {
        $funcs = $this->getRegisterEnqeueFunctions('js');

        if (isset($funcs['localize'])) {
            foreach ($localizationData as $objectName => $data) {
                if (!is_array($data)) {
                    throw new \InvalidArgumentException('Translation data for wpLocalizeScript must be an array.');
                }
                $funcs['localize']($handle, $objectName, $data);
            }
        }
    }

    /**
     * Attach arbitrary data to a specific asset handle (for extensibility).
     *
     * @param string $handle
     * @param array $data
     * @return void
     */
    public function addDataToHandle(string $handle, array $data): void
    {
        // Example: store or process data for the asset. Extend as needed.
        // This could be used for custom attributes, inline data, etc.
    }

    /**
     * Chainable method to add translation for an asset.
     *
     * @param string $src Asset source file name.
     * @param string $textDomain Text domain for translation.
     * @return self
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
     * Add an asset (CSS/JS) to the queue of assets to be rendered.
     * If cache busting is enabled, it will use the CacheBustManager to get the correct file name.
     * Module will be assumed if the file exists in the cachebust manifest and not explicitly set.
     *
     * @param string $handle The handle name for the script or style.
     * @param string $src The source URL of the script or style.
     * @param array $deps An array of dependencies for the script or style.
     * @param bool|null $module Whether to add the "module" attribute to the script tag.
     * @return void
     */
    public function addAsset(string $handle, string $src, array $deps = [], ?bool $module = null): void
    {
        $this->validateAddAssetParams($handle, $src);

        $func = $this->getRegisterEnqeueFunctions(
            $this->getFileType($src, $handle)
        );

        $module = $module ?? $this->isModule($src, $handle);
        $fullSrc = $this->getAssetUrl($src);

        $func['register']($handle, $fullSrc, $deps);

        if ($module === true) {
            $this->addAttributesToScriptTag($handle, ['type' => 'module']);
        }

        $func['enqueue']($handle);
    }

    /**
     * Validate parameters for adding an asset.
     *
     * @param string $handle
     * @param string $src
     * @return void
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
            throw new \RuntimeException(
                'Dist directory is not set. Please set it using setDistDirectory() method.'
            );
        }
    }

    /**
     * Get the register, enqueue, and optional localize functions based on file type.
     *
     * @param string $type The file type ('js' or 'css').
     * @return array Associative array with 'register', 'enqueue', and optionally 'localize'.
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
     * Add attributes to the script tag for a given handle.
     *
     * @param string $handle
     * @param array $attributes
     * @return void
     */
    private function addAttributesToScriptTag(string $handle, array $attributes): void
    {
        $this->wpService->addFilter(
            'script_loader_tag',
            function ($tag, $tagHandle) use ($handle, $attributes) {
                if ($tagHandle === $handle) {
                    foreach ($attributes as $key => $value) {
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
     * Get the file type extension from the source string.
     *
     * @param string $src
     * @param string $handle
     * @return string
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
     * Get the URL of an asset with optional cache busting.
     *
     * @param string $src
     * @return string
     */
    private function getAssetUrl(string $src): string
    {
        return $this->wpService->getTemplateDirectoryUri()
            . self::$assetsDistPath
            . ($this->cacheBustManager ? $this->cacheBustManager->name($src) : $src);
    }

    /**
     * Determine if the script should be treated as a module.
     *
     * @param string $src
     * @param string $handle
     * @return bool
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
