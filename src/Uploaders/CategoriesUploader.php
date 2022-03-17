<?php


namespace Aniart\BitrixUploader\Uploaders;


use Aniart\BitrixUploader\DTO\AbstractDataTransferObject;
use Aniart\BitrixUploader\DTO\CatalogGroup;
use Aniart\BitrixUploader\DTO\CatalogProperty;
use Aniart\BitrixUploader\Loggers\LoggerInterface;
use Aniart\BitrixUploader\Models\BitrixEntity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Contracts\AttributeFamily as AttributeFamilyContract;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Category\Contracts\Category;
use Webkul\Category\Repositories\CategoryRepository;

final class CategoriesUploader extends AbstractBitrixEntityUploader
{
    /**
     * @var CategoryRepository
     */
    private $categoriesRepository;
    /**
     * @var AttributeFamilyRepository
     */
    private $attributeFamiliesRepository;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->categoriesRepository = app(CategoryRepository::class);
        $this->attributeFamiliesRepository = app(AttributeFamilyRepository::class);
    }

    protected function query(): Builder
    {
        return BitrixEntity::query()->where([
            'entity_type' => CatalogGroup::class
        ]);
    }

    protected function doFlush(): void
    {
        $this->logger()->wln()->info('Удаление старых категорий товаров...');
        $this->query()->update(['bagisto_id' => null]);
        $this->truncateTableWhere('categories', [['id', '>', 1]]);
        $this->logger()->success('Успех!');

        $this->logger()->wln()->info('Сброс привязки товаров к семейству атрибутов...');
        DB::table('products')->update(['attribute_family_id' => null]);
        $this->logger()->success('Успех!');

        $this->logger()->wln()->info('Удаление старых семейств атрибутов...');
        $this->truncateTableWhere('attribute_families', [['id', '>', 1]]);
        $this->truncateTableWhere('attribute_groups', [['attribute_family_id', '>', 1]]);
        $this->logger()->success('Успех!');
    }

    /**
     * @param AbstractDataTransferObject|CatalogGroup $category
     * @return Model|Category
     */
    protected function saveDTO(AbstractDataTransferObject $category): Model
    {
        [$filterable, $familyProps] = $this->explodeGroupProps($category);

        $attributes = [
            'position' => $category->sort,
            'status' => $category->isActive,
            'parent_id' => 1,
            'locale' => 'all',
            'name' => $category->name,
            'slug' => $category->code,
            'attributes' => $filterable
        ];

        $attrFamily = $this->createAttributeFamily($category->name, $category->code, $familyProps);
        $attributes['additional'] = json_encode([
            'attribute_family_id' => $attrFamily->id
        ]);

        $categoryModel = $this->categoriesRepository->create($attributes);

        foreach($category->groups as $groupId){
            $childGroup = $this->query()->firstWhere(['entity_id' => $groupId]);
            if($childGroup?->bagisto_id){
                $this->categoriesRepository->update([
                    'parent_id' => $categoryModel->id
                ], $childGroup->bagisto_id);
            } else {
                $this->saveEntity($childGroup);
            }
        }

        return $categoryModel;
    }

    private function explodeGroupProps(CatalogGroup $category): array
    {
        $familyProps = $filterable = [];
        foreach($category->props as $prop){
            if($catalogProperty = $this->getPropertyEntity((int)$prop['propId'])){
                $familyProps[] = $catalogProperty->bagisto_id;
                if($prop['isFilterable']){
                    $filterable[] = $catalogProperty->bagisto_id;
                }
            }
        }

        $familyProps = array_map(fn($id) => ['id' => $id], array_unique($familyProps));

        return [$filterable, $familyProps];
    }

    private function getPropertyEntity(int $propId): ?BitrixEntity
    {
        static $properties;
        $result = $properties[$propId] ?? null;

        if($result !== false){
            $catalogProperty = BitrixEntity::query()->where([
                'entity_type' => CatalogProperty::class,
                'entity_id' => $propId
            ])->first();

            $result = $properties[$propId] = $catalogProperty ?? false;
        }

        return ($result ?: null);
    }

    private function createAttributeFamily(string $name, string $code, array $familyProps): AttributeFamilyContract|AttributeFamily
    {
        $familyData = [
            'code' => $code,
            'name' => $name,
            'attribute_groups' => $this->createAttributeGroups($familyProps),
            'status' => 1
        ];

        return $this->attributeFamiliesRepository->create($familyData);
    }

    private function createAttributeGroups(array $familyProps): array
    {
        $attributeGroups = [
            CatalogUploader::getMainGroupAttributesData(),
            CatalogUploader::getMarketingFeaturesData(),
            CatalogUploader::getClassifierGroupAttributesData(),
            CatalogUploader::getFilesGroupAttributesData(),
            [
                'code' => 'special',
                'name' => 'Special attributes',
                'position' => 5,
                'custom_attributes' => $familyProps
            ],
            CatalogUploader::getSEOAttributesData(6)
        ];

        return $attributeGroups;
    }
}