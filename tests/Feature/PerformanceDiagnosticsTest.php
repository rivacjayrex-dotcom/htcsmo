<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagnostics_only_execute_the_requested_read_only_probes(): void
    {
        DB::enableQueryLog();
        DB::flushQueryLog();

        try {
            $this->artisan('smo:check-performance', ['--samples' => 3])
                ->expectsOutput('Database location: local')
                ->assertSuccessful();
            $queries = DB::getQueryLog();
            $this->assertCount(3, $queries);
            $this->assertSame(['SELECT 1'], array_values(array_unique(array_column($queries, 'query'))));
        } finally {
            DB::disableQueryLog();
        }
    }

    public function test_invalid_sample_counts_do_not_query_the_database(): void
    {
        DB::enableQueryLog();
        DB::flushQueryLog();

        try {
            foreach (['0', '11', 'invalid'] as $samples) {
                $this->artisan('smo:check-performance', ['--samples' => $samples])
                    ->expectsOutput('--samples must be an integer between 1 and 10.')
                    ->assertExitCode(2);
            }
            $this->assertCount(0, DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
        }
    }
}
