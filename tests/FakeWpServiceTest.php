<?php

declare(strict_types=1);

namespace WpUtilService\Tests;

use PHPUnit\Framework\TestCase;

class FakeWpServiceTest extends TestCase
{
    public function testCacheGetReportsWhetherTheKeyExists(): void
    {
        $service = new FakeWpService(new HandlingFakeWpService());
        $found = null;

        $this->assertFalse($service->wpCacheGet(key: 'key', found: $found));
        $this->assertFalse($found);

        $service->wpCacheSet('key', false);

        $this->assertFalse($service->wpCacheGet(key: 'key', found: $found));
        $this->assertTrue($found);
    }
}
