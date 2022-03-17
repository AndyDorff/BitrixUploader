<?php


namespace Aniart\BitrixUploader\Models;


use Aniart\BitrixUploader\DTO\AbstractDataTransferObject;
use Illuminate\Database\Eloquent\Model;

final class BitrixEntity extends Model
{
    public $table = 'bx_upload_entities';

    protected $fillable = [
        'id', 'entity_id', 'entity_type', 'data', 'version', 'bagisto_id'
    ];

    protected $casts = [
        'data' => 'array'
    ];

    public static function fromDto(AbstractDataTransferObject $dto): self
    {
        return new self([
            'entity_id' => $dto->id,
            'entity_type' => get_class($dto),
            'data' => $dto->toArray(),
            'version' => ''
        ]);
    }

    public function toDto(): AbstractDataTransferObject
    {
        return (new $this->entity_type($this->data));
    }
}