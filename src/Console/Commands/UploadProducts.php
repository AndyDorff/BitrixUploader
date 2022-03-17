<?php

namespace Aniart\BitrixUploader\Console\Commands;

use AndyDorff\SherpaXML\SherpaXML;
use Aniart\BitrixUploader\Loggers\ArtisanCommandLogger;
use Aniart\BitrixUploader\Services\UploadProductsBuilder;
use Aniart\BitrixUploader\Services\UploadProductsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class UploadProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bx:upload 
        {--disk=local : disk where xml placement}
        {--file=bitrix/mti.xml : file path relative to disk where xml placement}
        {--products=0 : Set the number limitation of uploaded products}
        {--just-parse : Will be only parse xml file and save result into bx_entities table}
        {--just-upload : Will be only upload entities from bx_entities table to Bagisto}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $logger = new ArtisanCommandLogger($this);
        $uploadService = (new UploadProductsBuilder($logger))
            ->productsLimit((int)$this->option('products'))
            ->justParse((bool)$this->option('just-parse'))
            ->justUpload((bool)$this->option('just-upload'))
            ->build();

        try{
            $filePath = $this->option('file');
            $storageDisk = $this->option('disk');
            if($storageDisk === 'local'){
                $storagePath = Storage::disk('local')->path($filePath);
            } else {
                $fileName = pathinfo($filePath, PATHINFO_BASENAME);
                $storagePath = Storage::disk('local')->path('bitrix/'.$fileName);
                Storage::disk('local')->writeStream(
                    $storagePath,
                    Storage::disk($storageDisk)->readStream($filePath)
                );
            }
            $uploadService->upload($storagePath);
        } catch (\Throwable $e) {
            $logger->wln(false)->critical(
                $e->getFile().' '.$e->getLine().': '.$e->getMessage()
            );
        }
    }
}
