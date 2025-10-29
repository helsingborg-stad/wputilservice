<?php

declare(strict_types=1);

namespace WpUtilService\Features;

/**
 * Context helper for asset-specific operations in the fluent API.
 */
class EnqueueAssetContext
{
    /** @var EnqueueManager */
    private EnqueueManager $manager;

    /** @var string */
    private string $handle;

    /**
     * Constructor for context helper.
     *
     * @param EnqueueManager $manager
     * @param string $handle
     */
    public function __construct(EnqueueManager $manager, string $handle)
    {
        $this->manager = $manager;
        $this->handle = $handle;
    }

    /**
     * Attach translation data to the asset.
     *
     * @param array $localizationData Array of localization data, e.g. ['key' => __('value', 'domain')]
     * @return EnqueueManager Chainable manager
     */
    public function translation(array $localizationData): EnqueueManager
    {
        $this->manager->addTranslationToHandle($this->handle, $localizationData);
        return $this->manager;
    }

    /**
     * Attach arbitrary data to the asset.
     *
     * @param array $data Arbitrary data array
     * @return EnqueueManager Chainable manager
     */
    public function data(array $data): EnqueueManager
    {
        $this->manager->addDataToHandle($this->handle, $data);
        return $this->manager;
    }
}
