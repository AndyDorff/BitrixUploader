<?php


namespace Aniart\BitrixUploader\Uploaders;


use Aniart\BitrixUploader\Loggers\ArtisanCommandLogger;
use Aniart\BitrixUploader\Loggers\LoggerInterface;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;

abstract class AbstractUploader
{
    private ?ProgressBar $progressBar;

    public function __construct(
        private LoggerInterface $logger
    ){
        $this->initProgressBar();
    }

    private function initProgressBar(): void
    {
        if($this->logger instanceof ArtisanCommandLogger){
            $this->progressBar = $this->logger->command()->getOutput()->createProgressBar();
            $this->progressBar->setFormat("<fg=yellow>[%elapsed:6s%/~ %memory:6s%]</> <fg=blue>%bar%</> <info>%current%</info>/%max%: %message% \n\n");
        }
    }

    final protected function progressBar(): ?ProgressBar
    {
        return $this->progressBar;
    }

    final protected function logger(): LoggerInterface
    {
        return $this->logger;
    }

    final public function run(): void
    {
        $this->flush();
        $this->doRun();
    }

    final public function flush(): void
    {
        $this->logger->info('Очистка данных...');

        $this->doFlush();

        $this->logger->success('Очистка данных завершена');
    }

    abstract protected function doFlush(): void;
    abstract protected function doRun(): void;

    final protected function truncateTable(string $table): void
    {
        DB::table($table)->delete();
        DB::statement("ALTER TABLE `$table` AUTO_INCREMENT = 1;");
    }

    final protected function truncateTableWhere(string $table, array $where): void
    {
        $query = DB::table($table);
        if(isset($where[0]) && is_array($where[0])){
            foreach($where as $wh){
                $query->where(...$wh);
            }
        } else {
            $query->where($where);
        }
        $query->delete();
        //Установим автоинкремент
        $ai = (DB::table($table)->orderBy('id', 'desc')->first(['id'])?->id ?? 0) + 1;
        DB::statement("ALTER TABLE `$table` AUTO_INCREMENT = $ai;");
    }
}