<?php


namespace Aniart\BitrixUploader\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Http;
use Webkul\Product\Repositories\ProductImageRepository;
use Webkul\Product\Repositories\ProductRepository;

class UploadProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * @var \Illuminate\Contracts\Foundation\Application|mixed|ProductRepository
     */
    private ProductRepository $productsRepository;

    public function __construct(
        private int $productId,
        private array $attributes,
        private array $filesToUpload
    ){
    }

    public function handle()
    {
        $this->productsRepository = app(ProductRepository::class);

        $this->productsRepository->update($this->attributes, $this->productId);

        $this->uploadProductRegisteredFiles();
    }

    private function uploadProductRegisteredFiles(): void
    {
        UploadProductFilesJob::dispatch($this->productId, $this->filesToUpload)
            ->onConnection('database')
            ->onQueue('bx_uploader');
    }
}