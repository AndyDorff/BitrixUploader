<?php


namespace Aniart\BitrixUploader\DTO;


class CatalogGroup extends AbstractDataTransferObject
{
    public ?string $name = null;
    public bool $isActive = true;
    public int $sort =  1;
    public ?string $code = null;
    public string $picture = '';

    public array $groups = [];
    public array $props = [];
}