<?php


namespace Aniart\BitrixUploader\Handlers;


use AndyDorff\SherpaXML\Handler\AbstractHandler;
use AndyDorff\SherpaXML\Misc\ParseResult;
use Aniart\BitrixUploader\Loggers\ArtisanCommandLogger;
use Aniart\BitrixUploader\Loggers\LoggerInterface;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

class ProductsListHandler extends AbstractHandler
{
    private ?ProgressBar $progressBar = null;

    public function __construct(
        private ProductHandler $productHandler
    ){
        parent::__construct();
    }

    public function handle(LoggerInterface $logger, ParseResult $parseResult)
    {
        $logger->info('Сохранение информации о товарах...');
        if($logger instanceof ArtisanCommandLogger){
            $this->initProgressBar($logger->command());
            $parseResult->payload['progress_bar'] = $this->progressBar;
        }
    }

    private function initProgressBar(Command $command)
    {
        $this->progressBar = $command->getOutput()->createProgressBar();
        $this->progressBar->setFormat("<fg=yellow>[%elapsed:6s%/~ %memory:6s%]</> <fg=blue>%bar%</> <info>%current%</info>/~: %message% \n\n");
    }

    public function completed(): void
    {
        //дозагружаем несохраненные остатки
        $this->productHandler->saveCsvToDB();
        $this->progressBar?->finish();
    }

}