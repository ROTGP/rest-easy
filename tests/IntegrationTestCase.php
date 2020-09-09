<?php

namespace ROTGP\RestEasy\Test;

use ROTGP\RestEasy\Test\Models\User;

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

    protected function asUser($id) {
        return $this->actingAs(User::find($id));
    }
}
