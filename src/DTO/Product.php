<?php


namespace Aniart\BitrixUploader\DTO;


class Product extends AbstractDataTransferObject
{
    public string $name = '';
    public array $groups = [];
    public string $picture = '';
    public array $properties = [];
    public string $price = '0.00';
    public string $quantity = '0';
    public string $width = '';
    public string $height = '';
    public string $length = '';
}