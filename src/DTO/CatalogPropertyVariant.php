<?php


namespace Aniart\BitrixUploader\DTO;


class CatalogPropertyVariant extends AbstractDataTransferObject
{
    public string $value;
    public bool $isDefault = false;
    public int $sort = 1;
}