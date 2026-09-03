<?php

declare(strict_types=1);

namespace WpUtilService\Features\Enqueue;

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
     * @param string $objectName      The global object name for the translation.
     * @param array $localizationData Array of localization data, e.g. ['key' => __('value', 'domain')]
     * @return EnqueueManager Chainable manager
     */
    public function translation(null|string $objectName, array $localizationData): EnqueueManager
    {
        // Only execute if the manager's conditional state allows it
        if ($this->manager->shouldExecute()) {
            $this->manager->addTranslationToHandle($this->handle, $objectName, $localizationData);
        }

        return $this->manager; // Return the manager instance for chaining
    }

    /**
     * Attach arbitrary data to the asset.
     *
     * @param array $data Arbitrary data array
     * @return EnqueueManager Chainable manager
     */
    public function data(null|string $objectName, array $data): EnqueueManager
    {
        // Only execute if the manager's conditional state allows it
        if ($this->manager->shouldExecute()) {
            $this->manager->addDataToHandle($this->handle, $objectName, $data);
        }
        return $this->manager;
    }

    /**
     * Retrieve the EnqueueManager instance.
     *
     * @return EnqueueManager
     */
    public function getManager(): EnqueueManager
    {
        return $this->manager;
    }
}
