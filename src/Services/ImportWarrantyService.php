<?php

namespace Aniart\BitrixUploader\Services;

use Illuminate\Support\Facades\DB;

class ImportWarrantyService
{
    /**
     * @var mixed
     */
    private $attribute_id;
    private $locale;
    private $channel;

    public function __construct() {

    }

    public function updateWarranty($path) {
        $this->parser = \XMLReader::open($path);
        if (! $this->parser ) {
            throw new \Exception("Error while opening {$path}");
        }
        // find warranty attribute id
        $this->attribute_id = DB::table('attributes')
            ->where('code', 'guarantee')->first()->id;
        $this->locale = app()->getLocale() ?? 'ua';
        $this->channel = core()->getCurrentChannelCode() ?? core()->getDefaultChannelCode() ?? 'default';
        // find item
        // call upd
        /**
         * 1. 1st read after Predlozhenie - ID aka SKU. get it
         * 2. 2nd  ЗначенияСвойства -> read() ->id == 5182
         * 3. Exit via ПакетПредложений
         */
        $items_node = "Предложение";
        $end_node = "ПакетПредложений";
        $cnt = 10000000;
        $xml = $this->parser;
        while (true) {// read adnvance stepping in; next skipping subtree
            while (strcmp($xml->name, $end_node)) {
                $xml->read();continue;
            }
            // found entry
            while (strcmp($xml->name, $items_node)) {
                $xml->read();continue;
            }
            // found items pool // Предложение->Ид
            // Предложение->ЗначенияСвойств->ЗначенияСвойства->Ид 5182
            while ( strcmp($xml->name, $end_node) ) {
                $name = $xml->name;
                if ( strcmp($xml->name, $items_node) ) {
                    $xml->read();
                    continue;
                }
                $t = \XMLReader::XML($xml->readOuterXml());
                $sku = $this->findItem('Ид', $t);
                $attrValue = $this->findItem(['ЗначенияСвойств' ,'ЗначенияСвойства', 'Ид'], $t, 5182);
                if ($sku && ($attrValue || !strcmp($attrValue, '0')) ) {
                    $this->update($sku, $attrValue);
                } else {
                    throw new \Exception("Parse error, node values not found");
                }

                $t->close();
                $xml->next();
            }
            // exiting
            $xml->close();
            break;
        }
    }

    private function findItem($key, \XMLReader &$xml, $value = null): mixed {
        if (!is_array($key)) {
            $a = $xml->name;
            while ($a != $key) {
                $a = $xml->name;
                $xml->read();
            }
        } else { // find leftmost
            foreach ($key as $k) {
                $a = $xml->name;
                while ($a != $k) {
                    $a = $xml->name;
                    $xml->read();
                }
            }
            $a = $xml->value;
            while ($a != $value) {
                $xml->read();
                $a = $xml->value;
            }

            $a = $xml->name;
            while ($a != 'Значение') {
                $a = $xml->name;
                $xml->read();
            }
        }

        return $xml->value;
    }

    // mb wrap to array, call as stack
    private function update($sku, $attrValue) {
        $val = DB::table('product_attribute_values')
            ->join('products', function($join) use ($sku) {
                $join->on('products.id', '=', 'product_attribute_values.product_id')
                    ->where('products.sku', $sku);
            })->first();
        //  exists
        if ($val) {
            /*DB::table('product_attribute_values')
                ->join('products', function($join) use ($sku) {
                    $join->on('products.id', '=', 'product_attribute_values.product_id')
                        ->where('products.sku', $sku);
                })->where('product_attribute_values.attribute_id', $this->attribute_id)
            ->update(['product_attribute_values.text_value' => $attrValue]);*/
            DB::table('product_attribute_values')
                ->updateOrInsert(['product_attribute_values.product_id' => $val->id,
                    'product_attribute_values.attribute_id' => $this->attribute_id
                    ],[
                    'product_attribute_values.locale' => $this->locale,
                    'product_attribute_values.channel' => $this->channel,
                    'product_attribute_values.text_value' => $attrValue
                ]);
        } else {
            throw new \Exception('Product not found, aborting, XML product version' .
                ' might be different.');
            /*DB::table('product_attribute_values')
                ->insert([
                    'product_attribute_values.product_id' => $val->id,
                    'product_attribute_values.attribute_id' => $this->attribute_id,
                    'product_attribute_values.locale' => $this->locale,
                    'product_attribute_values.channel' => $this->channel,
                    'product_attribute_values.text_value' => $attrValue
                ]);*/
        }
    }
}