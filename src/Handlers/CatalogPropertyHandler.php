<?php


namespace Aniart\BitrixUploader\Handlers;


use AndyDorff\SherpaXML\Misc\ParseResult;
use Aniart\BitrixUploader\DTO\CatalogProperty;
use Symfony\Component\Console\Helper\ProgressBar;

class CatalogPropertyHandler extends AbstractDataTransferObjectHandler
{
    private ?ProgressBar $progressBar = null;
    private ?CatalogProperty $catalogProperty = null;

    public function handle(CatalogProperty $property, ParseResult $parseResult)
    {
        $this->catalogProperty = $property;
        $this->progressBar = $parseResult->payload['progress_bar'] ?? null;
    }

    public function completed(): void
    {
        $this->saveDto($this->catalogProperty);

        if($this->progressBar){
            $this->progressBar->setMessage($this->catalogProperty->name);
            $this->progressBar->advance();
        }
    }
}