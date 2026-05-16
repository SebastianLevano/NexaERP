<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateUserManualCommand extends Command
{
    protected $signature = 'docs:user-manual {--output= : Custom output path}';

    protected $description = 'Generate the per-role user manual PDF (storage/app/docs/manual-de-usuario.pdf).';

    public function handle(): int
    {
        $output = $this->option('output') ?: storage_path('app/docs/manual-de-usuario.pdf');

        File::ensureDirectoryExists(dirname($output));

        $pdf = Pdf::loadView('pdf.user-manual')
            ->setPaper('a4', 'portrait');

        File::put($output, $pdf->output());

        $size = number_format(filesize($output) / 1024, 1).' KB';
        $this->info("✓ Manual generado: {$output} ({$size})");

        return self::SUCCESS;
    }
}
