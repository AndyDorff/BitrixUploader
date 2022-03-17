<?php


namespace Aniart\BitrixUploader\DTO;


class CatalogPropertyValue extends AbstractDataTransferObject
{
    public string $propId;
    public array $values = [];
    public string $type = '';
    public string $desc = '';
}