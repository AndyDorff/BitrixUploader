<?php


namespace Aniart\BitrixUploader\Interpreters;


use AndyDorff\SherpaXML\Interpreters\AbstractInterpreter;
use AndyDorff\SherpaXML\SherpaXML;
use Aniart\BitrixUploader\DTO\CatalogPropertyValue;
use Aniart\BitrixUploader\DTO\Product;

class ProductInterpreter extends AbstractInterpreter
{
    public function className(): string
    {
        return Product::class;
    }

    public function interpret(SherpaXML $xml)
    {
        $simpleXml = new \SimpleXMLElement($xml->xmlReader()->readOuterXml());

        $product = new Product();
        $product->id = $simpleXml->Ид;
        $product->name = $simpleXml->Наименование;
        $product->groups = $this->parseGroups($simpleXml->Группы);
        $product->picture = $simpleXml->Картинка;
        $product->properties = $this->parseProperties($simpleXml->ЗначенияСвойств);
        $product->price = $simpleXml->Цены[0]?->Цена?->ЦенаЗаЕдиницу ?? '0.00';
        $product->quantity = $simpleXml->Количество;
        $product->width = $simpleXml->Ширина;
        $product->height = $simpleXml->Высота;
        $product->length = $simpleXml->Длина;

        return $product;
    }

    private function parseGroups(\SimpleXMLElement $groups): array
    {
        $result = [];
        foreach($groups as $group){
            $result[] = $group->Ид;
        }

        return $result;
    }

    private function parseProperties(\SimpleXMLElement $properties): array
    {
        $result = [];
        foreach($properties->ЗначенияСвойства as $property){
            $propertyValue = new CatalogPropertyValue([
                'propId' => $property->Ид,
                'values' => $this->parsePropertyValues($property),
                'type' => $property->Тип,
                'desc' => $property->ЗначениеСвойства
            ]);
            $result[$propertyValue->propId] = $propertyValue;
        }

        return $result;
    }

    private function parsePropertyValues(\SimpleXMLElement $property): array
    {
        $result = [];
        if($property->ЗначениеСвойства->hasChildren()){
            foreach($property->ЗначениеСвойства as  $el){
                $result[] = [
                    'value' => $el->Значение,
                    'desc' => $el->Описание
                ];
            }
        } else {
            foreach($property->Значение as $el){
                $result[] = [
                    'value' => strval($el),
                    'desc' => ''
                ];
            }
        }

        return $result;
    }
}