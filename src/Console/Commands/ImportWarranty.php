<?php

namespace Aniart\BitrixUploader\Console\Commands;

use Aniart\BitrixUploader\Services\ImportWarrantyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportWarranty extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bx:upload-warranty';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update warranty';
    private $importService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->importService = \app(ImportWarrantyService::class);
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = \base_path() . '/' . "storage/app/bitrix/mti.xml";
        try {
            $this->importService->updateWarranty($path);
        } catch (\Throwable $e) {
            $msg = $this::class . " failed with:\n" .
                $e->getMessage() . "\n";
            $this->error($msg);
            Log::error($msg . $e->getTraceAsString());
            $this->line($e->getTraceAsString());
        }
    }
}
