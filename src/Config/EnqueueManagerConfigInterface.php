<?php

namespace Wputilservice\Config;

interface EnqueueManagerConfigInterface
{
  /* Setters */ 
  public function setCacheBustState(bool $cacheBust): EnqueueManagerConfigInterface;
  public function setDistDirectory(string $distDirectory): EnqueueManagerConfigInterface;
  public function setManifestName(string $manifestName): EnqueueManagerConfigInterface;

  /* Getters */
  public function getIsCacheBustEnabled(): bool;
  public function getDistDirectory(): string;
  public function getManifestName(): string;
}
