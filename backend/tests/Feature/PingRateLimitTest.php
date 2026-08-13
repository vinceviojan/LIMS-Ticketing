<?php

namespace Tests\Feature;

use Tests\TestCase;

class PingRateLimitTest extends TestCase
{
    public function test_ping_is_rate_limited()
    {
        // Hit the ping route 30 times (should return 200)
        for ($i = 0; $i < 30; $i++) {
            $response = $this->get('/api/ping');
            $response->assertStatus(200);
        }

        // The 31st time should return 429 Too Many Requests
        $response = $this->get('/api/ping');
        $response->assertStatus(429);
    }
}
