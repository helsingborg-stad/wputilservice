<?php

use Wputilservice\Config\EnqueueManagerConfigInterface;
use \WpUtilService\Features\EnqueueManager;
use WpUtilService\Features\CacheBustManager;

trait Enqueue
{
  use WpServiceTrait;

  public function getEnqueueManager(EnqueueManagerConfigInterface $config): EnqueueManager
  {
    if($config->getIsCacheBustEnabled()) {
      $cacheBustManager = new CacheBustManager(
        $this->getWpService()
      );
      $cacheBustManager->setManifestPath($config->getDistDirectory());
      $cacheBustManager->setManifestName($config->getManifestName());
    }

    return (new EnqueueManager(
      $this->getWpService(),
      $cacheBustManager ?? null
    ))->setDistDirectory($config->getDistDirectory());
  }
}