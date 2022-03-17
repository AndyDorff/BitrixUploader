<?php


namespace Aniart\BitrixUploader\Uploaders;


use Aniart\BitrixUploader\DTO\AbstractDataTransferObject;
use Aniart\BitrixUploader\DTO\CatalogProperty;
use Aniart\BitrixUploader\DTO\CatalogPropertyVariant;
use Aniart\BitrixUploader\Loggers\LoggerInterface;
use Aniart\BitrixUploader\Models\BitrixEntity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Repositories\AttributeRepository;

final class AttributesUploader extends AbstractBitrixEntityUploader
{
    private AttributeRepository $attributesRepository;
    private array $catalogPropertyVariants = [];

    private static array $mapCodes = [
        'cml2_active' => 'status',
        'cml2_detail_text' => 'description',
        'cml2_preview_text' => 'short_description',
    ];

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->attributesRepository = app(AttributeRepository::class);
    }

    protected function doFlush(): void
    {
        //Удаляем все атрибуты, кроме системных
        $this->logger()->wln()->info('Удаление старых атрибутов...');
        $this->truncateTableWhere('attributes', ['is_user_defined' => true]);
        $this->logger()->success('Успех!');

        $this->logger()->wln()->info('Удаление неактуальных вариантов атрибутов...');
        $this->truncateTableWhere('bx_upload_entities', ['entity_type' => CatalogPropertyVariant::class]);
        $this->logger()->success('Успех!');
    }

    protected function query(): Builder
    {
        return BitrixEntity::query()->where([
            'entity_type' => CatalogProperty::class
        ]);
    }

    /**
     * @param CatalogProperty $dto
     * @return Model
     */
    protected function saveDTO(AbstractDataTransferObject $dto): Model
    {
        $code = $this->getAttributeCode($dto->code ?: $dto->id);

        if($attribute = $this->attributesRepository->findOneWhere(['code' => $code])){
            return $attribute;
        }

        $attributes = [
            'code' => $code,
            'admin_name' => $dto->name,
            'type' => $this->mapPropertyType($dto),
            'position' => $dto->sort,
            'is_required' => $dto->isMandatory,
            'is_unique' => false,
            'validation' => null,
            'value_per_local' => false,
            'value_per_channel' => false,
            'is_filterable' => $dto->isFilterable,
            'is_configurable' => false,
            'is_visible_on_front' => rand(0, 1),
            'is_user_defined' => true,
            'swatch_type' => 'dropdown',
            'use_in_flat' => $code === 'size',
            'is_comparable' => false,
            'options' => $this->mapPropertyVariants($dto)
        ];

        return $this->attributesRepository->create($attributes);
    }

    private function mapPropertyType(CatalogProperty $catalogProperty): string
    {
        $type = 'text';
        if($catalogProperty->type === $catalogProperty::TYPE_LIST){
            $type = ($catalogProperty->isMultiple ? 'multiselect' : 'select');
        } elseif ($catalogProperty->type === $catalogProperty::TYPE_FILE){
            $type = 'file';
        } elseif ($catalogProperty->type === $catalogProperty::TYPE_STRING){
            $type = ($catalogProperty->extType === 'HTML' ? 'textarea' : 'text');
        }

        return $type;
    }

    private function mapPropertyVariants(CatalogProperty $catalogProperty): array
    {
        $this->catalogPropertyVariants = [];
        return array_map(function (array $variant){
            $this->catalogPropertyVariants[$variant['id']] = $variant;
            return [
                'admin_name' => $variant['value'],
                'swatch_value' => $variant['id'],
                'sort_order' => $variant['sort'],
            ];
        }, $catalogProperty->variants ?? []);
    }

    /**
     * @param Model $model
     * @param AbstractDataTransferObject $dto
     * @return string
     */
    protected function getProgressBarMessage(Model $model, AbstractDataTransferObject $dto): string
    {
       return $model->admin_name;
    }

    public static function getAttributeCode(string $code): string
    {
        $code = strtolower($code);
        if(isset(self::$mapCodes[$code])){
            $code = self::$mapCodes[$code];
        }

        return $code;
    }
}