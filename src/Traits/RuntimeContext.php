<?php

trait RuntimeContext
{
  use WpServiceTrait;

  public function getRuntimeContextManager(): RuntimeContextManager
  {
    return new RuntimeContextManager($this->getWpService());
  }
}