<?php

namespace ROTGP\RestEasy\Test;

use Illuminate\Foundation\Testing\RefreshDatabase;

class IntegrationTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }
}
