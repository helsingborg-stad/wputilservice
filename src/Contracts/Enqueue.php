<?php

namespace WpUtilService\Contracts;

use EnqueueManagerInter
interface Enqueue
{
    public function getEnqueueManager(): EnqueueManager;
}
