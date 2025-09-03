<?php

namespace Wputilservice\Config;

use Wputilservice\Config\EnqueueManagerConfigInterface as I;

class EnqueueManagerConfig implements I
{
  protected bool    $cacheBust      = true;
  protected string  $distDirectory  = '/assets/dist/';
  protected string  $manifestName   = 'manifest.json';

  public function setCacheBustState(bool $cacheBust): I
  {
    $this->cacheBust = $cacheBust;
    return $this;
  }

  public function setDistDirectory(string $distDirectory): I
  {
    $this->distDirectory = $distDirectory;
    return $this;
  }

  public function setManifestName(string $manifestName): I
  {
    $this->manifestName = $manifestName;
    return $this;
  }

  public function getIsCacheBustEnabled(): bool
  {
    return $this->cacheBust;
  }

  public function getDistDirectory(): string
  {
    return $this->distDirectory;
  }

  public function getManifestName(): string
  {
    return $this->manifestName;
  }
}