<?php

declare(strict_types=1);

namespace WpUtilService\Config;

/**
 * Interface for EnqueueManagerConfig.
 */
interface EnqueueManagerConfigInterface
{
  // Setters
  /**
   * Set cache busting state.
   */
  public function setCacheBustState(bool $cacheBust): EnqueueManagerConfigInterface;

  /**
   * Set distribution directory.
   */
  public function setDistDirectory(string $distDirectory): EnqueueManagerConfigInterface;

  /**
   * Set manifest name.
   */
  public function setManifestName(string $manifestName): EnqueueManagerConfigInterface;

  // Getters
  /**
   * Get cache busting enabled state.
   */
  public function getIsCacheBustEnabled(): bool;

  /**
   * Get distribution directory.
   */
  public function getDistDirectory(): string;

  /**
   * Get manifest name.
   */
  public function getManifestName(): string;
}
