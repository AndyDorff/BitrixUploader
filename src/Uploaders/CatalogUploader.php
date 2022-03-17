<?php


namespace Aniart\BitrixUploader\Uploaders;


use Aniart\BitrixUploader\DTO\Catalog;
use Aniart\BitrixUploader\Loggers\LoggerInterface;
use Aniart\BitrixUploader\Models\BitrixEntity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Contracts\AttributeFamily;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Category\Contracts\Category;
use Webkul\Category\Repositories\CategoryRepository;

final class CatalogUploader extends AbstractUploader
{
    private CategoryRepository $categoriesRepository;
    private AttributeFamilyRepository $attributeFamiliesRepository;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->categoriesRepository = app(CategoryRepository::class);
        $this->attributeFamiliesRepository = app(AttributeFamilyRepository::class);
    }

    public static function getMainGroupAttributesData(): array
    {
        return [
            'code' => 'main',
            'name' => 'Main attributes',
            'position' => 1,
            'custom_attributes' => [
                ['code' => 'sku'],
                ['code' => 'name'],
                ['code' => 'status'],
                ['code' => 'visible_individually'],
                ['code' => 'short_description'],
                ['code' => 'description'],
                ['code' => 'price'],
                ['code' => 'weight']
            ]
        ];
    }

    public static function getMarketingFeaturesData(): array
    {
        return [
            'code' => 'features',
            'name' => 'Features',
            'position' => 2,
            'custom_attributes' => [
                ['code' => 'new'],
                ['code' => 'featured'],
            ]
        ];
    }

    public static function getClassifierGroupAttributesData(): array
    {
        return [
            'code' => 'vendor',
            'name' => 'Vendor attributes',
            'position' => 3,
            'custom_attributes' => [
                ['code' => 'brand_id'],
                ['code' => 'model'],
                ['code' => 'partno'],
                ['code' => 'cml2_article'],
                ['code' => 'product_type'],
                ['code' => 'country'],
                ['code' => 'guarantee'],
                ['code' => 'tnved']
            ]
        ];
    }

    public static function getFilesGroupAttributesData(): array
    {
        return [
            'code' => 'files',
            'name' => 'Additional files',
            'position' => 4,
            'custom_attributes' => [
                ['code' => 'more_photo'],
                ['code' => 'file'],
            ]
        ];
    }

    public static function getSEOAttributesData(int $position = 5): array
    {
        return [
            'code' => 'seo',
            'name' => 'Search Engine Optimization',
            'position' => 5,
            'custom_attributes' => [
                ['code' => 'url_key'],
                ['code' => 'meta_title'],
                ['code' => 'meta_keywords'],
                ['code' => 'meta_description']
            ]
        ];
    }

    protected function doFlush(): void
    {
        $this->logger()->wln()->info('Удаление каталога товаров...');

        $this->truncateTableWhere('categories', ['id' => 1]);
        $this->logger()->success('Успех!');

        DB::table('products')->update(['attribute_family_id' => null]);

        $this->truncateTableWhere('attribute_families', ['id' => 1]);
        $this->truncateTableWhere('attribute_groups', ['attribute_family_id' => 1]);

        $this->logger()->success('Успех!');
    }

    protected function doRun(): void
    {
        /**
         * @var BitrixEntity $entity
         */
        $entity = BitrixEntity::query()->firstWhere([
            'entity_type' => Catalog::class
        ]);
        /**
         * @var Catalog $catalog
         */
        $catalog = $entity->toDto();
        $this->createCatalog($catalog);
        $this->saveAttributeFamily($catalog);
    }

    private function createCatalog(Catalog $catalog): Category
    {
        app(Category::class)::unguard();
        $category = $this->categoriesRepository->create([
            'id' => 1,
            'position' => 1,
            'status' => 1,
            'parent_id' => null,
        ]);
        app(Category::class)::reguard();
        $this->categoriesRepository->update([
            'locale' => 'all',
            'name' => $catalog->name,
            'slug' => $catalog->id,
            'additional' => json_encode(['code' => $catalog->id])
        ], 1);

        return $category;
    }

    private function saveAttributeFamily(Catalog $catalog)
    {
        $this->attributeFamiliesRepository->getModel()->unguard();
        $familyData = [
            'id' => 1,
            'code' => $catalog->id,
            'name' => $catalog->name,
            'status' => 1,
            'attribute_groups' => [
                self::getMainGroupAttributesData(),
                self::getMarketingFeaturesData(),
                self::getClassifierGroupAttributesData(),
                self::getFilesGroupAttributesData(),
                self::getSEOAttributesData()
            ],
        ];
        //TODO reguard() вызывается прямо в методе create репозитория и с этим нужно что-то делать
        $this->attributeFamiliesRepository->create($familyData);
    }
}