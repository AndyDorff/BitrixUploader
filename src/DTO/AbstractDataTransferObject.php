<?php


namespace Aniart\BitrixUploader\DTO;


use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

abstract class AbstractDataTransferObject implements Arrayable, Jsonable
{
    public ?string $id = null;

    public function __construct(array $properties = [])
    {
        $this->initProperties($properties);
    }

    private function initProperties(array $properties): void
    {
        foreach($properties as $key => $value){
            if(property_exists($this, $key)){
                $this->{$key} = $value;
            }
        }
    }

    public function version(): string
    {
        return crc32(serialize($this->toArray()));
    }

    public function toArray(): array
    {
        return $this->convertToArray($this, true);
    }

    private function convertToArray($value, $self = false): mixed
    {
        return match(true){
            (($value instanceof Arrayable) && !$self) => $this->convertToArray($value->toArray()),
            is_object($value) => array_map([$this, 'convertToArray'], get_object_vars($value)),
            is_array($value) => array_map([$this, 'convertToArray'], $value),
            default => $value
        };
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}