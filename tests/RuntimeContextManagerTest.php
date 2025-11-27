<?php

declare(strict_types=1);

namespace WpUtilService\Tests;

use PHPUnit\Framework\TestCase;
use WpUtilService\Features\RuntimeContextManager;

class RuntimeContextManagerTest extends TestCase
{
    public function testTooLongPathIsNormalized()
    {
        $context = new RuntimeContextManager();
        $context->setPath('/root/wp-content/plugins/my-plugin/src/php');
        $this->assertEquals('/root/wp-content/plugins/my-plugin/', $context->getNormalizedRootPath());
    }
}
