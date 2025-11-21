<?php
declare(strict_types=1);

namespace WpUtilService;

use WpService\WpService;

trait WpServiceTrait
{
    private WpService $wpService;

    protected function setWpService(WpService $wpService): void
    {
        $this->wpService = $wpService;
    }

    public function getWpService(): WpService
    {
        return $this->wpService;
    }
}
