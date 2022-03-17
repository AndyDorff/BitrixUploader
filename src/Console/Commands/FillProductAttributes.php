<?php

namespace Aniart\BitrixUploader\Console\Commands;

use Aniart\BitrixUploader\DTO\CatalogProperty;
use Aniart\BitrixUploader\DTO\Product;
use Aniart\BitrixUploader\Models\BitrixEntity;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PDO;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Product\Models\Product as ProductModel;
use Webkul\Product\Models\ProductAttributeValue;
use Webkul\Product\Repositories\ProductAttributeValueRepository;

class FillProductAttributes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bx:fill-attribute {code*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private ProductAttributeValueRepository $attributeValueRepository;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        private array $attributes = []
    ) {
        parent::__construct();

        $this->attributeValueRepository = app(ProductAttributeValueRepository::class);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Start to fill product attribute values from bx upload table');

        $this->initAttributes();
        $query = BitrixEntity::query()
            ->select(['id', 'data', 'bagisto_id'])
            ->where('entity_type', Product::class);

        $bar = $this->output->createProgressBar($query->count());

        Schema::disableForeignKeyConstraints();
        $query->chunk(250, function (Collection $bxProducts) use ($bar, &$i){
            DB::beginTransaction();
            foreach($bxProducts as $bxProduct){
                $this->fillProductAttribute($bxProduct);
                $bar->advance();
            }
            DB::commit();
        });
        Schema::enableForeignKeyConstraints();

        $bar->finish();
        $this->info('Done!');
    }

    private function initAttributes(): void
    {
        $attributeCodes = $this->argument('code');
        $attributes = Attribute::query()->whereIn('code', $attributeCodes)
            ->get()
            ->keyBy('code');

        foreach($attributeCodes as $attrCode){
            if(!($attribute = $attributes->get($attrCode))){
                $this->warn('Attribute with code = "'.$attrCode.'" not found and will be skipped');
                continue;
            }
            $bxProperty = BitrixEntity::query()->firstWhere([
                'entity_type' => CatalogProperty::class,
                'bagisto_id' => $attribute->id
            ]);
            if(!$bxProperty){
                $this->warn('Property with Bagisto id = '.$attribute->id.' not found');
            }

            $this->attributes[$attrCode] = [
                'model' => $attribute,
                'bx_id' => $bxProperty->entity_id,
                'bx_variants' => collect($bxProperty['data']['variants'] ?? [])->keyBy('id')->all()
            ];
        }
    }

    private function fillProductAttribute(BitrixEntity $bxProduct): void
    {
        $attributeValues = [];
        foreach($this->attributes as $attrCode => $attrData){
            $c = microtime(true);
            $bxProductProperty = $bxProduct['data']['properties'][$attrData['bx_id']] ?? null;
            if(!$bxProductProperty){
                continue;
            }

            $attributeValues[$attrCode] = match ($attrData['model']->type){
                'select' => $this->getAttributeValueId($attrCode, current($bxProductProperty['values'])['value'] ?? ''),
                'multiselect' => array_map(fn($value) => $this->getAttributeValueId($attrCode, $value['value']), $bxProductProperty['values']),
                default => current($bxProductProperty['values'])['value'] ?? ''
            };
        }

        $this->saveProductAttributeValues($bxProduct->bagisto_id, $attributeValues);
    }

    private function getAttributeValueId(string $attrCode, string $bxValueId): ?int
    {
        static $attributeValues;
        $attributeValues = $attributeValues ?? [];
        $attributeValues[$attrCode] = $attributeValues[$attrCode] ?? [];

        if(!array_key_exists($bxValueId, $attributeValues[$attrCode])){
            $bxValue = $this->attributes[$attrCode]['bx_variants'][$bxValueId]['value'] ?? null;
            if($bxValue){
                $attrOption = AttributeOption::query()
                    ->whereHas('attribute', fn(Builder $query) => $query->where('code', $attrCode))
                    ->firstWhere('admin_name', $bxValue);
                if(!$attrOption){
                    $this->warn('Attribute option for bx value = "'.$bxValue.'" not found');
                }
            }

            $attributeValues[$attrCode][$bxValueId] = isset($attrOption) ? $attrOption->id : null;
        }

        return $attributeValues[$attrCode][$bxValueId];
    }

    private function saveProductAttributeValues(int $productId, array $attrValues): void
    {
        foreach ($this->attributes as $attrCode => $attrData) {
            $attribute = $attrData['model'];
            if (!isset($attrValues[$attribute->code])) {
                continue;
            }

            $attrValues[$attribute->code] = match ($attribute->type) {
                'boolean' => $attrValues[$attribute->code] ? 1 : 0,
                'price', 'date' => empty($attrValues[$attribute->code]) ? null : $attrValues,
                'multiselect', 'checkbox' => implode(',', $attrValues[$attribute->code]),
                default => $attrValues[$attribute->code]
            };

            $attributeValue = $this->attributeValueRepository->findOneWhere([
                'product_id'   => $productId,
                'attribute_id' => $attribute->id,
                'channel'      => null,
                'locale'       => null,
            ]);

            if ($attributeValue) {
                $this->attributeValueRepository->update([
                    ProductAttributeValue::$attributeTypeFields[$attribute->type] => $attrValues[$attribute->code],
                ], $attributeValue->id);
            } else {
                $this->attributeValueRepository->create([
                    'product_id'   => $productId,
                    'attribute_id' => $attribute->id,
                    'value'        => $attrValues[$attribute->code],
                    'channel'      => null,
                    'locale'       => null
                ]);
            }
        }

    }
}
