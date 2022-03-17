<?php


namespace Aniart\BitrixUploader\Interpreters;


use AndyDorff\SherpaXML\Interpreters\AbstractInterpreter;
use AndyDorff\SherpaXML\SherpaXML;
use Aniart\BitrixUploader\DTO\CatalogGroup;
use Aniart\BitrixUploader\DTO\CatalogGroupProperty;

class CatalogGroupInterpreter extends AbstractInterpreter
{
    public function className(): string
    {
        return CatalogGroup::class;
    }

    public function interpret(SherpaXML $xml)
    {
        $simpleXml = new \SimpleXMLElement($xml->xmlReader()->readOuterXml());

        $catalogGroup = $this->parseCatalogGroup($simpleXml);

        return $catalogGroup;
    }

    private function parseCatalogGroup(\SimpleXMLElement $simpleXml)
    {
        $catalogGroup = new CatalogGroup();
        $catalogGroup->id = (int)$simpleXml->Ид;
        $catalogGroup->name = $simpleXml->Наименование;
        $catalogGroup->isActive = current($simpleXml->БитриксАктивность) === 'true';
        $catalogGroup->sort = (int)$simpleXml->БитриксСортировка;
        $catalogGroup->code = $simpleXml->БитриксКод;
        $catalogGroup->picture = $simpleXml->БитриксКартинка;

        if(count($simpleXml->Группы->Группа ?? [])){
            foreach ($simpleXml->Группы->Группа as $groupXml){
                $catalogGroup->groups[] = $this->parseCatalogGroup($groupXml);
            }
        }

        if(count($simpleXml->СвойстваЭлементов->Свойство ?? [])){
            foreach ($simpleXml->СвойстваЭлементов->Свойство as $propXml){
                $catalogGroup->props[] = $this->parseCatalogProp($propXml);
            }
        }

        return $catalogGroup;
    }

    private function parseCatalogProp(\SimpleXMLElement $propXml)
    {
        $groupProperty = new CatalogGroupProperty([
            'propId' => (int)$propXml->Ид,
            'isFilterable' => (bool)$propXml->УмныйФильтр === true
        ]);

        return $groupProperty;
    }
}