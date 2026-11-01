<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use TestHelpers;

    /**
     * Seed default Tyro roles before every test so role-based middleware
     * and policy checks work correctly after RefreshDatabase wipes the DB.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }
}
