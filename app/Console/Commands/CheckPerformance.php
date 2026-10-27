<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckPerformance extends Command
{
    protected $signature = 'smo:check-performance {--samples=5 : Number of read-only database round trips (1-10)}';

    protected $description = 'Measure database connection and round-trip latency without changing application data';

    public function handle(): int
    {
        $samples = filter_var($this->option('samples'), FILTER_VALIDATE_INT);
        if ($samples === false || $samples < 1 || $samples > 10) {
            $this->error('--samples must be an integer between 1 and 10.');

            return self::INVALID;
        }

        $this->table(['Setting', 'Effective value'], [
            ['Environment', app()->environment()],
            ['Debug', config('app.debug') ? 'enabled' : 'disabled'],
            ['Cache', config('cache.default')],
            ['Sessions', config('session.driver')],
            ['Queue', config('queue.default')],
            ['CLI OPcache', extension_loaded('Zend OPcache') && ini_get('opcache.enable_cli') ? 'enabled' : 'disabled (web PHP may differ)'],
        ]);

        try {
            $connection = DB::connection();
            $host = $connection->getConfig('host');
            $local = $connection->getDriverName() === 'sqlite'
                || in_array($host, ['localhost', '127.0.0.1', '::1'], true);
            $this->line('Database location: '.($local ? 'local' : 'remote / non-loopback host'));

            $start = hrtime(true);
            $connection->getPdo();
            $connectMs = (hrtime(true) - $start) / 1_000_000;

            $times = [];
            for ($index = 0; $index < $samples; $index++) {
                $start = hrtime(true);
                $connection->select('SELECT 1', [], false);
                $times[] = (hrtime(true) - $start) / 1_000_000;
            }
        } catch (Throwable) {
            // Connection exception messages may contain infrastructure details.
            $this->error('Database connection failed. Check availability and network access. No data was changed.');

            return self::FAILURE;
        }

        $average = array_sum($times) / count($times);
        $this->table(['Measurement', 'Milliseconds'], [
            ['Connection setup', number_format($connectMs, 1)],
            ['SELECT 1 average', number_format($average, 1)],
            ['SELECT 1 minimum', number_format(min($times), 1)],
            ['SELECT 1 maximum', number_format(max($times), 1)],
        ]);
        $this->line('These are CLI/database timings, not browser page-load measurements.');

        if ($average >= 50) {
            $this->warn('Database round trips are expensive. 20 sequential queries at this average cost about '
                .number_format($average * 20 / 1000, 2).' seconds, plus connection setup and rendering.');
        }

        return self::SUCCESS;
    }
}
