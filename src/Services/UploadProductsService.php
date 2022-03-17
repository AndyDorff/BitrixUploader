<?php


namespace Aniart\BitrixUploader\Services;


use AndyDorff\SherpaXML\Parser;
use AndyDorff\SherpaXML\SherpaXML;
use Aniart\BitrixUploader\Handlers\CatalogGroupHandler;
use Aniart\BitrixUploader\Handlers\CatalogGroupsListHandler;
use Aniart\BitrixUploader\Handlers\CatalogHandler;
use Aniart\BitrixUploader\Handlers\CatalogPropertiesListHandler;
use Aniart\BitrixUploader\Handlers\CatalogPropertyHandler;
use Aniart\BitrixUploader\Handlers\ProductHandler;
use Aniart\BitrixUploader\Handlers\ProductsListHandler;
use Aniart\BitrixUploader\Interpreters\CatalogGroupInterpreter;
use Aniart\BitrixUploader\Interpreters\CatalogInterpreter;
use Aniart\BitrixUploader\Interpreters\CatalogPropertyInterpreter;
use Aniart\BitrixUploader\Interpreters\LoggerInterpreter;
use Aniart\BitrixUploader\Interpreters\ProductInterpreter;
use Aniart\BitrixUploader\Loggers\LoggerInterface;
use Aniart\BitrixUploader\Services\UploadProducts\UploadToBagistoService;
use Aniart\BitrixUploader\Services\UploadProducts\UploadXmlService;

final class UploadProductsService
{
    public function __construct(
        private UploadProductsConfig $config,
        private LoggerInterface $logger
    ){
    }

    public function upload(string $xmlPath): void
    {
        $this->logger->info('Начало загрузки товаров...');
        if($this->config->isJustUpload){
            $this->logger->warning('Парсинг файла отключен');
        } else {
            $this->uploadXml($xmlPath);
        }
        if($this->config->isJustParse){
            $this->logger->warning('Сохранение сущносетй Bagisto отключена');
        } else {
            $this->uploadToBagisto();
        }
        $this->logger->success('Товары успешно загружены');
    }

    private function uploadXml(string $xmlPath): void
    {
        $xml = $this->loadXml($xmlPath);
        $parser = new Parser([
            new LoggerInterpreter($this->logger),
            new CatalogInterpreter(),
            new CatalogPropertyInterpreter(),
            new CatalogGroupInterpreter(),
            new ProductInterpreter()
        ]);

        $xmlUploader = new UploadXmlService($parser, $this->logger);
        $xmlUploader->uploadXml($xml);
    }

    private function loadXml(string $xmlPath): SherpaXML
    {
        $xml = SherpaXML::open($xmlPath);

        $xml->on('/КоммерческаяИнформация', function(SherpaXML $xml){
            $xml->on('/Классификатор', function (SherpaXML $xml) {
                $xml->on('/', new CatalogHandler());
                $xml->on('/Свойства', function(SherpaXML $xml){
                    $xml->on('/', new CatalogPropertiesListHandler());
                    $xml->on('/Свойство', new CatalogPropertyHandler());
                });
                $xml->on('/Группы', function(SherpaXML $xml){
                    $xml->on('/', new CatalogGroupsListHandler());
                    $xml->on('/Группа', new CatalogGroupHandler());
                });
            });
            $xml->on('/ПакетПредложений/Предложения', function (SherpaXML $xml){
                $productHandler = new ProductHandler($this->config->productsLimit);
                $xml->on('/', new ProductsListHandler($productHandler));
                $xml->on('Предложение',$productHandler);
            });
        });

        return $xml;
    }

    private function uploadToBagisto()
    {
        $bagistoUploader = new UploadToBagistoService($this->logger);
        $bagistoUploader->upload($this->config->productsLimit);
    }
}