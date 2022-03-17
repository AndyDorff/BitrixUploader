<?php


namespace Aniart\BitrixUploader\Interpreters;


use AndyDorff\SherpaXML\Interpreters\AbstractInterpreter;
use AndyDorff\SherpaXML\SherpaXML;
use Aniart\BitrixUploader\DTO\Catalog;

class CatalogInterpreter extends AbstractInterpreter
{
    public function className(): string
    {
        return Catalog::class;
    }

    public function interpret(SherpaXML $xml)
    {
        $catalog = new Catalog();

        $xml->on('Ид', fn() => $catalog->id = $xml->xmlReader()->readInnerXml());
        $xml->on('Наименование', fn() => $catalog->name = $xml->xmlReader()->readInnerXml());
        $xml->on('Описание', fn() => $catalog->description = $xml->xmlReader()->readInnerXml());

        return $catalog;
    }

    public function isReady(Catalog $catalog): bool
    {
        return isset($catalog->id, $catalog->name, $catalog->description);
    }
}