<?php


namespace Aniart\BitrixUploader\Loggers;


interface LoggerInterface extends \Psr\Log\LoggerInterface
{
    const SUCCESS = 'success';

    //without new line
    public function wln(): static;
    public function success(string $string, array $context = []): void;
}