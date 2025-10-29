<?php 

namespace WpUtilService;

use WpService\WpService;

trait WpServiceTrait
{
    public function __construct(private WpService $wpService)
    {
        $this->wpService = $wpService;
    }

    public function getWpService(): WpService
    {
        return $this->wpService;
    }
}