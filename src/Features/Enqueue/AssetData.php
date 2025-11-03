<?php

declare(strict_types=1);

namespace WpUtilService\Features\Enqueue;

/**
 * Manages asset data.
 */
class AssetData
{
    /**
     * @var array Track used data object names to enforce uniqueness
     */
    private array $usedDataObjectNames = [];

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
     *  - only JS assets accept data
     *  - data object names must be unique across all assets
     *
     * @param string $handle
     * @param string $objectName
     * @param array $data associative array to pass to wp_add_inline_script
     *
     * @throws \RuntimeException|\InvalidArgumentException
     */
    public function addDataToHandle(string $handle, ?string $objectName, array $data): void
    {
        //Create name if not provided
        if($objectName === null || $objectName === '') {
            $objectName = ucfirst($handle) . 'Data';
        }

        //Check if name is unique
        if (in_array($objectName, $this->usedDataObjectNames, true)) {
            throw new \RuntimeException("Data object name '{$objectName}' must be unique across all assets.");
        }

        //Store as used name
        $this->usedDataObjectNames[] = $objectName;

        //Get enqueue functions for asset type
        $funcs = $this->assetRegistrar->getRegisterEnqueueFunctions('js');

        //Check if data is supported
        if (isset($funcs['data'])) {
            if (!is_array($data) && !empty($data)) {
                throw new \InvalidArgumentException('Data must be an array and cannot be empty.');
            }
            $funcs['data']($handle, $objectName, $data);
        } else {
            throw new \RuntimeException('Data is not supported for this asset type.');
        }
    }
}
