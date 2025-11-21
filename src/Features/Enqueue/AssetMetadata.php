<?php

declare(strict_types=1);

namespace WpUtilService\Features\Enqueue;

/**
 * Manages asset metadata (localization and data).
 * Consolidates functionality from AssetLocalization and AssetData.
 */
class AssetMetadata
{
    /**
     * @var array Track used object names to enforce uniqueness
     */
    private array $usedObjectNames = [];

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
     * @param string|null $objectName
     * @param array $localizationData associative array to pass to wp_localize_script
     *
     * @throws \RuntimeException|\InvalidArgumentException
     */
    public function addTranslation(string $handle, null|string $objectName, array $localizationData): void
    {
        $this->addMetadata($handle, $objectName, $localizationData, 'translation', 'Localization');
    }

    /**
     * Attach data to a specific asset handle.
     *
     * Enforces:
     *  - only JS assets accept data
     *  - data object names must be unique across all assets
     *
     * @param string $handle
     * @param string|null $objectName
     * @param array $data associative array to pass to wp_add_inline_script
     *
     * @throws \RuntimeException|\InvalidArgumentException
     */
    public function addData(string $handle, null|string $objectName, array $data): void
    {
        $this->addMetadata($handle, $objectName, $data, 'data', 'Data');
    }

    /**
     * Generic method to add metadata (translation or data) to an asset.
     *
     * @param string $handle
     * @param string|null $objectName
     * @param array $metadata
     * @param string $type 'translation' or 'data'
     * @param string $defaultSuffix Suffix for auto-generated object names
     *
     * @throws \RuntimeException|\InvalidArgumentException
     */
    private function addMetadata(
        string $handle,
        null|string $objectName,
        array $metadata,
        string $type,
        string $defaultSuffix
    ): void {
        // Create name if not provided
        if ($objectName === null || $objectName === '') {
            $objectName = ucfirst($handle) . $defaultSuffix;
        }

        // Check if name is unique
        if (in_array($objectName, $this->usedObjectNames, true)) {
            throw new \RuntimeException(
                ucfirst($type) . " object name '{$objectName}' must be unique across all assets."
            );
        }

        // Store as used name
        $this->usedObjectNames[] = $objectName;

        // Get asset type and functions
        $assetType = $this->assetRegistrar->getAssetTypeForHandle($handle);
        $funcs = $this->assetRegistrar->getRegisterEnqueueFunctions($assetType);

        // Check if the metadata type is supported
        if (!isset($funcs[$type])) {
            throw new \RuntimeException(ucfirst($type) . ' is not supported for this asset type.');
        }

        // Validate metadata
        if (!is_array($metadata) && !empty($metadata)) {
            throw new \InvalidArgumentException(ucfirst($type) . ' data must be an array and cannot be empty.');
        }

        // Call the appropriate function
        $funcs[$type]($handle, $objectName, $metadata);
    }
}
