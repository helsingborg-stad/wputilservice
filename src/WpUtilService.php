<?php

namespace WpUtilService;

use WpService\WpService;
use WpUtilService\WpUtilServiceInterface;
use WpUtilService\Traits\Enqueue;
use WpUtilService\Traits\EnqueueTranslation;

class WpUtilService implements WpUtilServiceInterface
{
    use Enqueue;
    public function __construct(private WpService $wpService){}
}