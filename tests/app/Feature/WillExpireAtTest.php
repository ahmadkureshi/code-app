<?php

namespace Tests\Feature;

use Carbon\Carbon;
use DTApi\Helpers\TeHelper;
use Tests\TestCase;

class WillExpireAtTest extends TestCase
{
    public function testWillExpireAtMethod()
    {
        $dueTime = Carbon::now()->addHours(72);
        $createdAt = Carbon::now()->subHours(24);

        $result = TeHelper::willExpireAt($dueTime, $createdAt);

        $expected = $dueTime->copy()->subHours(48);

        $this->assertEquals($expected->format('Y-m-d H:i:s'), $result);
    }
}