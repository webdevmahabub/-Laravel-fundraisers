<?php

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

/*
 * RefreshDatabase (transaction-based) is unreliable with MySQL when vendor
 * migrations run. DatabaseMigrations runs a real migrate/rollback per suite
 * which is correct on MySQL without the pdo_sqlite driver.
 */
pest()->extend(TestCase::class)
    ->use(DatabaseMigrations::class)
    ->in('Feature', 'Unit');
