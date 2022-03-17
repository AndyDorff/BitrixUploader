<?php


namespace Aniart\BitrixUploader\Uploaders;


use Aniart\BitrixUploader\DTO\AbstractDataTransferObject;
use Aniart\BitrixUploader\DTO\CatalogGroup;
use Aniart\BitrixUploader\DTO\CatalogProperty;
use Aniart\BitrixUploader\DTO\Product;
use Aniart\BitrixUploader\Jobs\UploadProductFilesJob;
use Aniart\BitrixUploader\Jobs\UploadProductJob;
use Aniart\BitrixUploader\Loggers\LoggerInterface;
use Aniart\BitrixUploader\Models\BitrixEntity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Contracts\AttributeOption;
use Webkul\Attribute\Models\Attribute as AttributeModel;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Product\Repositories\ProductRepository;

final class ProductsUploader extends AbstractBitrixEntityUploader
{
    private ProductRepository $productsRepository;
    private CategoryRepository $categoryRepository;
    private AttributeFamilyRepository $attributeFamilyRepository;

    private array $attributes = [];
    private array $filesToUpload = [];

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);

        $this->attributeFamilyRepository = app(AttributeFamilyRepository::class);
        $this->productsRepository = app(ProductRepository::class);
        $this->categoryRepository = app(CategoryRepository::class);

        $this->logger()->wln()->info('Инициализация атрибутов товаров...');
        $this->initAttributes();
        $this->logger()->success('Успех!');
    }

    public function withLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    private function initAttributes(): void
    {
        app(Attribute::class)::query()->with(['options'])->each(function(Attribute|AttributeModel $attribute){
            $property = BitrixEntity::query()
                ->where(['entity_type' => CatalogProperty::class, 'bagisto_id' => $attribute->id])
                ->first();
            if($property){
                $attributeData = $attribute->only([
                    'id', 'code', 'type'
                ]);
                $attributeData['propId'] = $property->entity_id;
                if($attributeData['type'] === 'select' || $attributeData['type'] === 'multiselect'){
                    $attributeData['options'] = array_reduce($attribute->options->all(),
                        function (array $result, AttributeOption|\Webkul\Attribute\Models\AttributeOption $option){
                            $result[$option->swatch_value] = $option->id;
                            return $result;
                        }, []);
                }
                $this->attributes[$attributeData['id']] = $attributeData;
            }
        }, 100);
    }

    protected function doFlush(): void
    {
        $this->logger()->wln()->info('Удаление старых товаров...');
        $this->truncateTable('products');
        $this->logger()->success('Успех!');

        $this->logger()->wln()->info('Удаление старых файлов для товаров...');
        Storage::disk()->deleteDirectory('product');
        $this->logger()->success('Успех!');
    }

    protected function query(): Builder
    {
        return BitrixEntity::query()->where([
            'entity_type' => Product::class
        ]);
    }

    /**
     * @param AbstractDataTransferObject|Product $product
     * @return Model
     */
    protected function saveDTO(AbstractDataTransferObject $product): Model
    {
        $this->filesToUpload = [];
        $categories = $this->getCategories($product) ?: [1];

        $attributes = [
            'type' => 'simple',
            'attribute_family_id' => $this->getAttributeFamilyId($categories),
            'sku' => $product->id
        ];

        $productModel = $this->productsRepository->create($attributes);
        $attributes = $this->completeProductAttributes($productModel, [
            'categories' => $categories,
            'inventories' => [1 => $product->quantity],
            'price' => $product->price,
            'locale' => 'ua',
            'channel' => 'default',
            'name' => $product->name,
        ], $product->properties);

        $this->registerFilesToUpload('', [$product->picture]);

        UploadProductJob::dispatch($productModel->id, $attributes, $this->filesToUpload)
            ->onConnection('database')
            ->onQueue('bx_uploader');

        return $productModel;
    }

    /**
     * @param Model $model
     * @param AbstractDataTransferObject|Product $dto
     * @return string
     */
    protected function getProgressBarMessage(Model $model, AbstractDataTransferObject $dto): string
    {
        return $dto->name;
    }

    private function getAttributeFamilyId(array $categories): ?int
    {
        $category = current($categories);
        if($category){
            $familyId = $this->attributeFamilyRepository->findOneWhere([
                'code' => $this->categoryRepository->find($category)->translations->first()->slug
            ])?->id;
        }

        return $familyId ?? null;
    }

    private function getCategories(Product $product): array
    {
        $groups = array_map(fn($g) => current($g), $product->groups);

        $categoriesId = BitrixEntity::query()
            ->where('entity_type', CatalogGroup::class)
            ->whereIn('entity_id', $groups)
            ->get('bagisto_id')
            ->pluck('bagisto_id')
            ->toArray();


        return $categoriesId;
    }

    private function completeProductAttributes(
        \Webkul\Product\Contracts\Product | \Webkul\Product\Models\Product $productModel,
        array $attributes,
        array $properties
    ){
        foreach($this->getProductAttributes($productModel) as $attribute){
            $prop = $properties[$attribute['propId']];
            $value = $prop['values'][0]['value'];
            $attributes[$attribute['code']] = match ($attribute['type']){
                'multiselect', 'checkbox' => array_filter(array_map(fn($val) => $attribute['options'][$val['value']] ?? '', $prop['values'])),
                'select' => $attribute['options'][$value] ?? '',
                'file', 'image' => $this->registerFilesToUpload($attribute['code'], $prop['values']),
                'boolean' => ($value === 'true' ? 1 : 0),
                default => $value
            };
        }
        $attributes['url_key'] = Str::slug($attributes['cml2_article']);
        //TODO weight - системное, обязательное свойство
        $attributes['weight'] = 1;
        //TODO visible_individually - системное, обязательное свойство
        $attributes['visible_individually'] = 1;
        //TODO такое свойство используется Багисто, нужно будет придумать, что с ним делать
        unset($attributes['channels']);

        return $attributes;
    }

    private function getProductAttributes(\Webkul\Product\Contracts\Product | \Webkul\Product\Models\Product $product): array
    {
        static $attributes;
        $attributes = $attributes ?? [];

        $familyId = $product->attribute_family_id;

        if(!isset($attributes[$familyId])){
            $attributes[$familyId] = [];
            foreach($product->attribute_family->custom_attributes as $attribute){
                if(array_key_exists($attribute->id, $this->attributes)){
                    $attributes[$familyId][] = $this->attributes[$attribute->id];
                }
            }
        }

        return $attributes[$familyId];
    }

    private function registerFilesToUpload(string $attributeCode, array $values): string
    {
        $attributeCode = ($attributeCode === 'more_photo' ? '' : $attributeCode);
        $files = array_filter(array_map([$this, 'completeFileLink'], $values));
        if(!empty($files)){
            $this->filesToUpload[$attributeCode] = array_merge(
                $this->filesToUpload[$attributeCode] ?? [],
                $files
            );
        }

        return '';
    }

    private function completeFileLink(array|string $data): string
    {
        $linkToFile = is_array($data) ? $data['value'] : $data;

        return ( $linkToFile
            ? config('bitrix_uploader.files_source').'/'.$linkToFile
            : ''
        );
    }

}