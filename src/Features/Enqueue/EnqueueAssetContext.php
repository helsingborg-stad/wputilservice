<?php

declare(strict_types=1);

namespace WpUtilService\Features\Enqueue;

/**
 * Context helper for asset-specific operations in the fluent API.
 */
class EnqueueAssetContext {

    /** @var EnqueueManager */
    private EnqueueManager $manager;

    /** @var string */
    private string $handle;

    /** @var AssetLocalization|null */
    private ?AssetLocalization $assetLocalization = null;

    /** @var AssetData|null */
    private ?AssetData $assetData = null;

    /**
     * Track used object names for both translation and data
     * @var array
     */
    private array $usedObjectNames = [];

    /**
     * Constructor for context helper.
     *
     * @param EnqueueManager $manager
     * @param string $handle
     */
    public function __construct(EnqueueManager $manager, string $handle)
    {
        $this->manager = $manager;
        $this->handle  = $handle;
    }

    /**
     * Attach translation data to the asset.
     *
     * @param string $objectName      The global object name for the translation.
     * @param array $localizationData Array of localization data, e.g. ['key' => __('value', 'domain')]
     * @return EnqueueManager Chainable manager
     */
    public function translation(?string $objectName, array $localizationData): EnqueueManager
    {
        //Create name if not provided
        if($objectName === null || $objectName === '') {
            $objectName = ucfirst($this->handle) . 'Localization';
        }

        //Check if name is unique across both translation and data
        if (in_array($objectName, $this->usedObjectNames, true)) {
            throw new \RuntimeException("Object name '{$objectName}' must be unique across all assets (translation/data).");
        }
        $this->usedObjectNames[] = $objectName;
        
        if ($this->assetLocalization === null) {
            $this->assetLocalization = new AssetLocalization(
                $this->manager->getAssetRegistrar()
            );
        }

        $this->assetLocalization->addTranslationToHandle(
            $this->handle,
            $objectName,
            $localizationData
        );

        $this->usedObjectNames[] = $objectName;

        return $this->manager;
    }

    /**
     * Attach arbitrary data to the asset.
     *
     * @param array $data Arbitrary data array
     * @return EnqueueManager Chainable manager
     */
    public function data(?string $objectName, array $data): EnqueueManager
    {
        //Create name if not provided
        if($objectName === null || $objectName === '') {
            $objectName = ucfirst($this->handle) . 'Data';
        }

        //Check if name is unique across both translation and data
        if (in_array($objectName, $this->usedObjectNames, true)) {
            throw new \RuntimeException("Object name '{$objectName}' must be unique across all assets (translation/data).");
        }
        $this->usedObjectNames[] = $objectName;

        if (!isset($this->assetData)) {
            $this->assetData = new AssetData(
                $this->manager->getAssetRegistrar()
            );
        }

        $this->assetData->addDataToHandle(
            $this->handle,
            $objectName,
            $data
        );

        return $this->manager;
    }
}
