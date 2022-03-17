<?php


namespace Aniart\BitrixUploader\Services\UploadProducts;


use AndyDorff\SherpaXML\Parser;
use AndyDorff\SherpaXML\SherpaXML;
use Aniart\BitrixUploader\Loggers\LoggerInterface;
use Aniart\BitrixUploader\Models\BitrixEntity;
use Aniart\Integrations\Models\Integration;
use Aniart\Integrations\Models\IntegrationRelation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UploadXmlService
{
    public function __construct(
        private Parser $parser,
        private LoggerInterface $logger,
    ){}

    public function uploadXml(SherpaXML $xml): void
    {
        $this->clearTable();
        $this->parseXml($xml);
    }

    private function clearTable(): void
    {
        Schema::disableForeignKeyConstraints();
        $this->logger->wln()->info('Очистка временной таблицы...');
        DB::table((new BitrixEntity())->table)->truncate();
        $this->logger->success('Успех!');
        $this->logger->wln()->info('Очистка интеграционных таблиц...');
        DB::table((new IntegrationRelation())->table)->truncate();
        DB::table((new Integration())->table)->truncate();
        $this->logger->success('Успех!');
        Schema::enableForeignKeyConstraints();
    }

    private function parseXml(SherpaXML $xml): void
    {
        $this->logger->info('Начало обработки XML-файла...');
        $this->parser->parse($xml);
        $this->logger->success('XML-файл успешно обработан');
    }
}