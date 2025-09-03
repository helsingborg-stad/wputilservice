<?php

use \WpUtilService\Features\CacheBustManager;

trait CacheBust
{
    public function getCacheBustManager(string $manifestPath, string $manifestName): CacheBustManager
    {
      return (new CacheBustManager($this->getWpService()))
      ->setManifestPath($manifestPath)
      ->setManifestName($manifestName);
    }
}