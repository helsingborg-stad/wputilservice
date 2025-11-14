<?php
declare(strict_types=1);

namespace WpUtilService;

use WpService\WpService;
use WpUtilService\Traits\Enqueue;
use WpUtilService\WpUtilServiceInterface;

class WpUtilService implements WpUtilServiceInterface
{
    /* Include Traits (Features) */
    use Enqueue; // Provides enqueue() method

    public function __construct(
        private WpService $wpService,
    ) {}
}
