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

class UploadProductFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * @var \Illuminate\Contracts\Foundation\Application|mixed|ProductRepository
     */
    private ProductRepository $productsRepository;
    /**
     * @var \Illuminate\Contracts\Foundation\Application|mixed|ProductImageRepository
     */
    private ProductImageRepository $productImageRepository;

    public function __construct(
        private int $productId,
        private array $filesData
    ){
    }

    public function handle()
    {
        $this->productsRepository = app(ProductRepository::class);
        $this->productImageRepository = app(ProductImageRepository::class);

        $attributes = $files = [];

        foreach($this->filesData as $attributeCode => $attributeFiles){
            if($attributeCode){
                $attributes[$attributeCode] = $this->downloadFiles(array_splice($attributeFiles, 0, 1));
                $files[$attributeCode] = $attributes[$attributeCode];
            } else {
                $attributes['images'] = $this->downloadFiles($attributeFiles);
                $files['images'] = $attributes['images'];
            }
        }

        $this->saveProduct($attributes, $files);
    }

    private function downloadFiles(array $files): array
    {
        $uploadedFiles = [];
        $client = Http::withoutVerifying()->timeout(5);
        foreach($files as $linkToFile){
            $linkToFile = str_replace('https://', 'http://', $linkToFile);
            $response = $client->head($linkToFile);
            if($response->successful() && $response->header('Content-Length') > 0){
                $response = $client->get($linkToFile);
                $uploadedFiles[] = $this->createUploadedFile($response->body(), pathinfo($linkToFile, PATHINFO_FILENAME));
            }
        }

        return $uploadedFiles;
    }

    private function createUploadedFile(string $data, string $name = null): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'bx_');
        file_put_contents($path, $data);
        $name = $name ?? pathinfo($path, PATHINFO_BASENAME);

        return new UploadedFile($path, $name, null, null, true);
    }

    private function saveProduct(array $attributes, array $files)
    {
        $oldRequest = app('request');
        $newRequest = new Request(files:$files);
        $newRequest->setRouteResolver(fn() => (new Route('POST', '/', []))
            ->name('admin.catalog.products.massupdate')
            ->bind($newRequest)
        );

        app()->instance('request', $newRequest);

        $product = $this->productsRepository->update($attributes, $this->productId);
        $this->productImageRepository->uploadImages($attributes, $product);

        app()->instance('request', $oldRequest);
    }

}