<?php


namespace Aniart\BitrixUploader\Uploaders;


use Aniart\BitrixUploader\DTO\AbstractDataTransferObject;
use Aniart\BitrixUploader\Loggers\LoggerInterface;
use Aniart\BitrixUploader\Models\BitrixEntity;
use Aniart\Integrations\DTO\CreateRelationDTO;
use Aniart\Integrations\Services\IntegrationService;
use App\Services\IntegrationsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class AbstractBitrixEntityUploader extends AbstractUploader
{
    protected int $limit = 0;
    protected int $queryCount = 250;
    private IntegrationsService $integrationsService;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->integrationsService = app(IntegrationsService::class);
    }

    final protected function doRun(): void
    {
        $count = $this->query()->count();
        if($this->limit){
            $count = ($this->limit > $count) ? $count : $this->limit;
        }
        $this->progressBar()?->start($count);

        $this->query()->each(function(BitrixEntity $entity){
            $this->saveEntity($entity);
            if($this->limit && --$this->limit === 0){
                return false;
            }
        }, $this->queryCount);

        $this->progressBar()?->finish();
    }

    abstract protected function query(): Builder;

    final protected function saveEntity(BitrixEntity $entity): void
    {
        $dto = $entity->toDto();
        $model = $this->saveDTO($dto);

        $entity->update(['bagisto_id' => $model->id]);
        $this->integrationsService->replaceBitrixRelation(new CreateRelationDTO([
            'entity' => get_class($model),
            'internal_id' => $model->id,
            'external_id' => $dto->id,
        ]));

        $this->progressBar()?->setMessage($this->getProgressBarMessage($model, $dto));
        $this->progressBar()?->advance();
    }

    abstract protected function saveDTO(AbstractDataTransferObject $dto): Model;

    protected function getProgressBarMessage(Model $model, AbstractDataTransferObject $dto): string
    {
        return ($model->getAttribute('name') ?? '');
    }
}