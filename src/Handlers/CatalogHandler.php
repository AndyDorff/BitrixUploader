<?php


namespace Aniart\BitrixUploader\Handlers;


use AndyDorff\SherpaXML\Parser;
use Aniart\BitrixUploader\DTO\Catalog;
use Aniart\BitrixUploader\Loggers\LoggerInterface;

class CatalogHandler extends AbstractDataTransferObjectHandler
{
    public function handle(Catalog $catalog, Parser $parser, LoggerInterface $logger)
    {
        $logger->wln()->info('Сохранение информации о каталоге...');
        $this->saveDto($catalog);
        $logger->success('Успех!');
    }
}