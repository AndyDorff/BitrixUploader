<?php


namespace Aniart\BitrixUploader\DTO;


class CatalogProperty extends AbstractDataTransferObject
{
    const TYPE_STRING = 'S';
    const TYPE_LIST = 'L';
    const TYPE_FILE = 'F';
    const TYPE_NUMBER = 'N';

    public string $name = '';
    public string $code = '';
    public string $type = self::TYPE_STRING;
    public string $extType = '';
    public bool $isMultiple = false;
    public bool $isMandatory = false;
    public bool $isFilterable = false;
    public int $sort = 1;
    public string $defaultValue = '';
    public ?array $variants = null;
}
