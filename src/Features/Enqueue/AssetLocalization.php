<?php

declare(strict_types=1);

namespace WpUtilService\Features\Enqueue;

/**
 * Manages asset localization and translation data.
 */
class AssetLocalization
{

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
    public function addTranslationToHandle(string $handle, ?string $objectName, array $localizationData): void
    {
        //Get enqueue functions for asset type
        $funcs = $this->assetRegistrar->getRegisterEnqueueFunctions(
            $this->assetRegistrar->getAssetTypeForHandle($handle)
        );

        //Check if localization is supported
        if (isset($funcs['localize'])) {
            if (!is_array($localizationData) && !empty($localizationData)) {
                throw new \InvalidArgumentException('Localization data must be an array and cannot be empty.');
            }
            $funcs['localize']($handle, $objectName, $localizationData);
        } else {
            throw new \RuntimeException('Localization is not supported for this asset type.');
        }
    }
}
