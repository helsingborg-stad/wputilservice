<?php

declare(strict_types=1);

namespace WpUtilService\Features\Enqueue;

/**
 * Manages asset localization and translation data.
 */
class AssetLocalization
{
    /**
     * @var array Track used translation object names to enforce uniqueness
     */
    private array $usedTranslationObjectNames = [];

    /**
     * @var AssetRegistrar
     */
    private AssetRegistrar $assetRegistrar;

    /**
     * Constructor.
     *
     * @param AssetRegistrar $assetRegistrar
     */
    public function __construct(AssetRegistrar $assetRegistrar)
    {
        $this->assetRegistrar = $assetRegistrar;
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
        $assetType = $this->assetRegistrar->getAssetTypeForHandle($handle);

        if ($assetType === 'css') {
            throw new \RuntimeException('Cannot add translation to a CSS asset.');
        }

        if (in_array($objectName, $this->usedTranslationObjectNames, true)) {
            throw new \RuntimeException("Translation object name '{$objectName}' must be unique across all assets.");
        }

        $this->usedTranslationObjectNames[] = $objectName;

        $funcs = $this->assetRegistrar->getRegisterEnqueueFunctions('js');

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
        $assetType = $this->assetRegistrar->getAssetTypeForHandle($handle);
        if ($assetType === 'css') {
            throw new \RuntimeException('Cannot add data to a CSS asset.');
        }

        // Placeholder: store or process data for the asset (e.g. inline JSON, custom attributes)
        // e.g. $this->inlineData[$handle] = $data; — implement storage as needed.
    }
}
