<?php

namespace App\Console\Commands;

use App\Http\Controllers\FileUploadController;
use Illuminate\Console\Command;

class CleanupTempFiles extends Command
{
    protected $signature = 'files:cleanup';
    protected $description = 'Clean up temporary files older than 10 minutes';

    public function handle()
    {
        $controller = new FileUploadController();
        $controller->cleanup();
        $this->info('Temporary files cleaned up successfully.');
    }
}
