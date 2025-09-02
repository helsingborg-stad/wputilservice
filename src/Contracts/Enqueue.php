<?php

namespace WpUtilService\Contracts;

interface Enqueue
{
    public function enqueue(string $script, array $dependencies = [], string $version = null, bool $in_footer = false): void;
}
