<?php

namespace WpUtilService\Contracts;

class Enqueue
{
    public function enqueue(string $script, array $dependencies = [], string $version = null, bool $in_footer = false): void
    {
        // Implementation goes here
    }
}
