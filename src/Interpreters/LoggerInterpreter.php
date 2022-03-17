<?php


namespace Aniart\BitrixUploader\Interpreters;


use AndyDorff\SherpaXML\Interpreters\AbstractInterpreter;
use AndyDorff\SherpaXML\SherpaXML;
use Aniart\BitrixUploader\Loggers\LoggerInterface;

final class LoggerInterpreter extends AbstractInterpreter
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function className(): string
    {
        return LoggerInterface::class;
    }

    public function interpret(SherpaXML $xml)
    {
        return $this->logger;
    }
}