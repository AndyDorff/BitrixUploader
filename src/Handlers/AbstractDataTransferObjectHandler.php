<?php


namespace Aniart\BitrixUploader\Handlers;


use AndyDorff\SherpaXML\Handler\AbstractHandler;
use Aniart\BitrixUploader\DTO\AbstractDataTransferObject;
use Aniart\BitrixUploader\Models\BitrixEntity;
use Illuminate\Support\Str;

abstract class AbstractDataTransferObjectHandler extends AbstractHandler
{
    public function saveDto(AbstractDataTransferObject $dto)
    {
        try{
            $model = BitrixEntity::fromDto($dto);
            $model->save();
        } catch (\Throwable $e){
            if(Str::contains($e->getMessage(), 'Duplicate entry')){
                $model = BitrixEntity::query()->firstWhere(['entity_id' => $model->entity_id, 'entity_type' => $model->entity_type]);
            } else {
                throw $e;
            }
        }

        return $model;
    }
}