<?php


namespace Aniart\BitrixUploader\Services;


class UploadProductsConfig
{
    public function __construct(
        public int $productsLimit = 0,
        public bool $isJustParse = false,
        public bool $isJustUpload = false
    ){}
}