<?php


namespace Aniart\BitrixUploader\Handlers;



use AndyDorff\SherpaXML\Misc\ParseResult;
use AndyDorff\SherpaXML\Parser;
use Aniart\BitrixUploader\DTO\Product;
use Illuminate\Support\Facades\DB;

class ProductHandler extends AbstractDataTransferObjectHandler
{
    private const CSV_SEPARATOR = ';';
    private const CSV_ENCLOSURE = '\'';
    private const CSV_ESCAPE = '|';

    private int $count = 1;
    private $csv;

    public function __construct(
        private int $productsLimit
    ){
        parent::__construct();
        $this->initCsv();
    }

    private function initCsv(): void
    {
        $this->csv = fopen('php://memory', "w+");
        fputcsv($this->csv, [
            'entity_id', 'data'
        ], self::CSV_SEPARATOR, self::CSV_ENCLOSURE, self::CSV_ESCAPE);
    }

    public function handle(Product $product, ParseResult $parseResult, Parser $parser)
    {
        $progressBar = $parseResult->payload['progress_bar'] ?? null;
        if($progressBar){
            $progressBar->setMessage($product->name);
            $progressBar->advance();
        }

        $saveCount = $this->productsLimit ? ($this->productsLimit) : 500;
        if($saveCount > 500){
            $saveCount = 500;
        }

        $this->saveDtoToCsv($product);

        if($this->count % $saveCount === 0){
            $this->saveCsvToDB();
        }

        if($this->count - $this->productsLimit === 0){
            $parser->break();
        } else {
            $this->count++;
        }
    }

    public function saveCsvToDB()
    {
        fseek($this->csv, 0);
        $filePath = storage_path('containers/mysql/bx_uploader.csv');
        file_put_contents($filePath, $this->csv);

        DB::statement("
            LOAD DATA INFILE '/var/lib/mysql-files/bx_uploader.csv'
            REPLACE
            INTO TABLE `bx_upload_entities`
            FIELDS TERMINATED BY '".self::CSV_SEPARATOR."' 
            OPTIONALLY ENCLOSED BY '\\".self::CSV_ENCLOSURE."' 
            ESCAPED BY '".self::CSV_ESCAPE."' 
            LINES TERMINATED BY '\\n'
            IGNORE 1 LINES
            (entity_id, data)
            SET 
                entity_type = '".addslashes(Product::class)."',
                created_at = NOW(),
                updated_at = NOW() 
        ");

        unlink($filePath);
        fclose($this->csv);
        $this->initCsv();
    }

    private function saveDtoToCsv(Product $product): void
    {
        fputcsv($this->csv, [
           $product->id, $product->toJson()
        ], self::CSV_SEPARATOR, self::CSV_ENCLOSURE, self::CSV_ESCAPE);
    }
}