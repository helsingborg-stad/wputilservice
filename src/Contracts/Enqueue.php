<?php

namespace WpUtilService\Contracts;

use WpUtilService\Features\Enqueue\EnqueueManager;

/**
 * Interface for enqueue feature contract.
 */
interface Enqueue
{
    /**
     * Returns an EnqueueManager instance for managing asset enqueuing.
     *
     * @return EnqueueManager
     */
    public function enqueue(): EnqueueManager;
}
