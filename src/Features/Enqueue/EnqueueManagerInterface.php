<?php

declare(strict_types=1);

namespace WpUtilService\Features\Enqueue;

/**
 * Interface for EnqueueManager.
 */
interface EnqueueManagerInterface
{
    /**
     * Get the instance of the EnqueueManager.
     */
    public function enqueue(
        null|string $rootDirectory = null,
        null|string $distDirectory = null,
        null|string $manifestName = null,
        bool $cacheBust = true,
    ): self;

    /**
     * Set the dist directory and return this instance (fluent).
     */
    public function setDistDirectory(string $distDirectory): self;

    /**
     * Adds an asset (CSS/JS) by source path, with optional dependencies, version, and module flag.
     *
     * @param string      $src     Asset source path.
     * @param array       $deps    Dependencies.
     * @param string|null $version Optional version (currently unused).
     * @param bool|null   $module  Whether to treat as JS module.
     *
     * @return self
     */
    public function add(string $src, array $deps = [], null|string $version = null, null|bool $module = null): self;

    /**
     * Returns a context object for the last added asset, enabling .with()->... chaining.
     *
     * @param string|null $function Optional specific method to call on the context object (shortcut).
     */
    public function with(null|string $function = null, ...$args);

    /**
     * Alias for chaining convenience — requires with() to have been called first.
     *
     * @param string|null $function Optional specific method to call on the context object (shortcut).
     */
    public function and(null|string $function = null, ...$args);

    /**
     * Attach translation data to a specific asset handle.
     *
     * @param string $handle
     * @param string $objectName
     * @param array $localizationData
     */
    public function addTranslationToHandle(string $handle, string $objectName, array $localizationData): void;

    /**
     * Attach arbitrary data to a specific asset handle (for extensibility).
     *
     * @param string $handle
     * @param string|null $objectName
     * @param array $data
     */
    public function addDataToHandle(string $handle, null|string $objectName, array $data): void;
}
