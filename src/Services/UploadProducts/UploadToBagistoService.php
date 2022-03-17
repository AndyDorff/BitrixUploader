<?php


namespace Aniart\BitrixUploader\Services\UploadProducts;


use Aniart\BitrixUploader\Loggers\LoggerInterface;
use Aniart\BitrixUploader\Uploaders\AttributesUploader;
use Aniart\BitrixUploader\Uploaders\CatalogUploader;
use Aniart\BitrixUploader\Uploaders\CategoriesUploader;
use Aniart\BitrixUploader\Uploaders\ProductsUploader;
use Aniart\Integrations\DTO\CreateIntegrationDTO;
use Aniart\Integrations\Services\IntegrationService;

class UploadToBagistoService
{
    public function __construct(
        private LoggerInterface $logger
    ){}

    public function upload(int $productsLimit = 0): void
    {
        $this->createIntegration();
        $this->uploadAttributes();
        $this->uploadCatalog();
        $this->uploadCategories();
        $this->uploadProducts($productsLimit);
    }

    private function createIntegration(): void
    {
        $integrationService = app(IntegrationService::class);
        if(!$integrationService->findIntegration('bitrix')){
            $integrationService->createIntegration(new CreateIntegrationDTO([
                'id' => 'bitrix',
                'name' => 'Bitrix'
            ]));
        }
    }

    private function uploadCatalog(): void
    {
        $this->logger->info('Создание каталога для товарів...');
        (new CatalogUploader($this->logger))->run();
        $this->logger->success('Каталог товарів успешно создан!');
    }

    private function uploadAttributes(): void
    {
        $this->logger->info('Сохранение атрибутов товаров...');
        (new AttributesUploader($this->logger))->run();
        $this->logger->success('Атрибуты товаров успешно сохранены');
    }

    private function uploadCategories(): void
    {
        $this->logger->info('Сохранение категорий товаров...');
        (new CategoriesUploader($this->logger))->run();
        $this->logger->success('Категории товаров успешно сохранены');
    }

    private function uploadProducts(int $limit): void
    {
        $this->logger->info('Сохранение товаров...');
        (new ProductsUploader($this->logger))
            ->withLimit($limit)
            ->run();
        $this->logger->success('Товары успешно сохранены');
    }
}