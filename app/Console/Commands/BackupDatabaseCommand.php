<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'db:backup
        {--keep=14 : Days of backups to keep before pruning.}';

    protected $description = 'Dump the current database to storage/app/backups (gzipped) and prune old backups.';

    public function handle(): int
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (($config['driver'] ?? null) !== 'mysql') {
            $this->error("Solo se soporta MySQL en este comando (driver actual: {$config['driver']}).");

            return self::FAILURE;
        }

        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);

        $timestamp = Carbon::now()->format('Ymd-His');
        $filename = "nexaerp-{$timestamp}.sql.gz";
        $path = $backupDir.DIRECTORY_SEPARATOR.$filename;

        $command = sprintf(
            'mysqldump --no-tablespaces --single-transaction --quick -h%s -P%s -u%s %s %s | gzip > %s',
            escapeshellarg((string) $config['host']),
            escapeshellarg((string) $config['port']),
            escapeshellarg((string) $config['username']),
            $config['password'] !== '' && $config['password'] !== null
                ? '-p'.escapeshellarg((string) $config['password'])
                : '',
            escapeshellarg((string) $config['database']),
            escapeshellarg($path),
        );

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('Backup falló: '.$process->getErrorOutput());

            return self::FAILURE;
        }

        $size = number_format(filesize($path) / 1024, 1).' KB';
        $this->info("✓ Backup creado: {$filename} ({$size})");

        $this->prune((int) $this->option('keep'), $backupDir);

        return self::SUCCESS;
    }

    private function prune(int $keepDays, string $backupDir): void
    {
        $cutoff = Carbon::now()->subDays($keepDays);
        $deleted = 0;

        foreach (File::files($backupDir) as $file) {
            if (Carbon::createFromTimestamp($file->getMTime())->lt($cutoff)) {
                File::delete($file->getRealPath());
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->line("Eliminados {$deleted} backup(s) con más de {$keepDays} días.");
        }
    }
}
