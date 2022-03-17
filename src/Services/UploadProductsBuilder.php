<?php


namespace Aniart\BitrixUploader\Services;


use Aniart\BitrixUploader\Loggers\ArtisanCommandLogger;
use Aniart\BitrixUploader\Loggers\LoggerInterface;

final class UploadProductsBuilder
{
    private UploadProductsConfig $config;

    public function __construct(
        private LoggerInterface $logger
    ){
        $this->config = new UploadProductsConfig();
    }

    public function when(mixed $condition, callable $fnTrue = null, callable $fnFalse = null): self
    {
        call_user_func(boolval($condition) ? $fnTrue : $fnFalse, $this, $condition);

        return $this;
    }

    public function changeLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function productsLimit(int $productsLimit): self
    {
        $this->config->productsLimit = (($productsLimit >= 0) ? $productsLimit : 0);

        return $this;
    }

    public function justParse(bool $isJustParse = true): self
    {
        $this->config->isJustParse = $isJustParse;

        return $this;
    }

    public function justUpload(bool $isJustUpload = true): self
    {
        $this->config->isJustUpload = $isJustUpload;

        return $this;
    }

    public function build(): UploadProductsService
    {
        return new UploadProductsService($this->config, $this->logger);
    }
}