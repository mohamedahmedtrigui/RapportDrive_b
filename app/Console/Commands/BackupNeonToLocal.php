<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class BackupNeonToLocal extends Command
{
    protected $signature = 'backup:neon-to-local';

    protected $description = 'Copy production data from Neon (Postgres) into a local MySQL database, browsable via phpMyAdmin';

    protected const LOCAL_DATABASE = 'rapportdrive_prod_backup';

    // Business tables only — skips ephemeral framework tables (cache, jobs,
    // sessions) that carry nothing worth preserving.
    protected const TABLES = [
        'users',
        'dispatchers',
        'drivers',
        'zones',
        'reports',
        'report_entries',
        'notifications',
        'personal_access_tokens',
    ];

    public function handle(): int
    {
        $deployEnv = $this->readDeployEnv();

        if (! $deployEnv) {
            $this->error('.env.deploy not found at '.base_path('.env.deploy').' — fill it in first.');

            return self::FAILURE;
        }

        foreach (['DB_URL'] as $key) {
            if (empty($deployEnv[$key])) {
                $this->error("{$key} is missing or empty in .env.deploy.");

                return self::FAILURE;
            }
        }

        Config::set('database.connections.neon_source', [
            'driver' => 'pgsql',
            'url' => $deployEnv['DB_URL'],
            'sslmode' => $deployEnv['DB_SSLMODE'] ?? 'require',
            'search_path' => 'public',
            'charset' => 'utf8',
        ]);

        Config::set('database.connections.local_backup', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => self::LOCAL_DATABASE,
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'engine' => 'InnoDB',
        ]);

        $this->info('Rebuilding local schema in "'.self::LOCAL_DATABASE.'"...');
        DB::connection()->statement(
            'CREATE DATABASE IF NOT EXISTS `'.self::LOCAL_DATABASE.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        Artisan::call('migrate:fresh', [
            '--database' => 'local_backup',
            '--force' => true,
        ], $this->output);

        DB::connection('local_backup')->statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (self::TABLES as $table) {
            $this->info("Copying {$table}...");
            DB::connection('local_backup')->table($table)->truncate();

            DB::connection('neon_source')->table($table)->orderBy('id')->chunk(500, function ($rows) use ($table) {
                $rows = $rows->map(fn ($row) => (array) $row)->all();

                if ($rows !== []) {
                    DB::connection('local_backup')->table($table)->insert($rows);
                }
            });
        }

        DB::connection('local_backup')->statement('SET FOREIGN_KEY_CHECKS=1');

        $this->newLine();
        $this->info('Done. Browse "'.self::LOCAL_DATABASE.'" in phpMyAdmin at http://localhost/phpmyadmin');

        return self::SUCCESS;
    }

    private function readDeployEnv(): ?array
    {
        $path = base_path('.env.deploy');

        if (! file_exists($path)) {
            return null;
        }

        $vars = [];

        foreach (file($path) as $line) {
            if (preg_match('/^\s*([A-Z_]+)=(.*)$/', $line, $matches)) {
                $vars[$matches[1]] = trim($matches[2]);
            }
        }

        return $vars;
    }
}
