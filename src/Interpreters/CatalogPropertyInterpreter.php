<?php


namespace Aniart\BitrixUploader\Interpreters;


use AndyDorff\SherpaXML\Interpreters\AbstractInterpreter;
use AndyDorff\SherpaXML\SherpaXML;
use Aniart\BitrixUploader\DTO\CatalogProperty;
use Aniart\BitrixUploader\DTO\CatalogPropertyVariant;

class CatalogPropertyInterpreter extends AbstractInterpreter
{
    public function className(): string
    {
        return CatalogProperty::class;
    }

    public function interpret(SherpaXML $xml)
    {
        $catalogProperty = new CatalogProperty();

        $xml->on('/Ид', fn() => $catalogProperty->id = $xml->xmlReader()->readInnerXml());
        $xml->on('/Наименование', fn() => $catalogProperty->name = $xml->xmlReader()->readInnerXml());
        $xml->on('/Множественное', fn() => $catalogProperty->isMultiple = ($xml->xmlReader()->readInnerXml() === 'true'));
        $xml->on('/БитриксСортировка', fn() => $catalogProperty->sort = $xml->xmlReader()->readInnerXml());
        $xml->on('/БитриксКод', fn() => $catalogProperty->code = $xml->xmlReader()->readInnerXml());
        $xml->on('/БитриксТипСвойства', fn() => $catalogProperty->type = $xml->xmlReader()->readInnerXml());
        $xml->on('/БитриксРасширениеТипа', fn() => $catalogProperty->extType = $xml->xmlReader()->readInnerXml());
        $xml->on('/БитриксОбязательное', fn() => $catalogProperty->isMandatory = ($xml->xmlReader()->readInnerXml() === 'true'));
        $xml->on('/БитриксЗначениеПоУмолчанию', fn() => $catalogProperty->defaultValue = $xml->xmlReader()->readInnerXml());
        $xml->on('/БитриксФильтрРазрешен', fn() => $catalogProperty->isFilterable =( $xml->xmlReader()->readInnerXml() === 'true'));

        $xml->on('/ВариантыЗначений/Вариант', function(\SimpleXMLElement $el) use ($catalogProperty){
            $catalogProperty->variants[] = new CatalogPropertyVariant([
                'id' => current($el->Ид),
                'value' => current($el->Значение),
                'isDefault' => current($el->ПоУмолчанию),
                'sort' => current($el->Сортировка),
            ]);
        });

        return $catalogProperty;
    }
}