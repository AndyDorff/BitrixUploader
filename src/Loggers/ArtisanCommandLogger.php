<?php


namespace Aniart\BitrixUploader\Loggers;


use Illuminate\Console\Command;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use function Symfony\Component\String\s;

/**
 * Class ArtisanCommandLogger
 * @package Aniart\BitrixUploader\Loggers
 */
final class ArtisanCommandLogger extends AbstractLogger implements LoggerInterface
{
    use WithoutNewLineModeTrait;

    private Command $command;
    private ?string $timeFormat = 'Y-m-d H:i:s';

    private static array $methodStyles = [
        'info' => 'info',
        'error' => 'error',
        'warn' => 'warning',
    ];

    public function __construct(Command $command)
    {
        $this->command = $command;
        $this->initStyles();
    }

    private function initStyles(): void
    {
        if (! $this->command->getOutput()->getFormatter()->hasStyle('warning')) {
            $style = new OutputFormatterStyle('yellow');
            $this->command->getOutput()->getFormatter()->setStyle('warning', $style);
        }
    }

    public function withoutTime(): self
    {
        $this->timeFormat = null;

        return $this;
    }

    public function setTimeFormat(string $timeFormat): self
    {
        $this->timeFormat = $timeFormat;

        return $this;
    }

    public function command(): Command
    {
        return $this->command;
    }

    public function success(string $string, array $context = []): void
    {
        $this->log('success', $string, $context);
    }

    public function log($level, $message, array $context = array()): void
    {
        $method = match ($level) {
            LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::EMERGENCY, LogLevel::ERROR => 'error',
            LogLevel::WARNING => 'warn',
            LoggerInterface::SUCCESS, LogLevel::INFO => 'info',
            default => 'line'
        };

        $this->doLog($method, $level, $message, $context);
    }

    private function doLog(string $method, string $level, string $message, array $context = []): void
    {
        $verbosity = null;
        if(array_key_exists('verbosity', $context)){
            $verbosity = $context['verbosity'];
            unset($context['$verbosity']);
        }

        if(!$this->wlnInProcess()){
            $message = ($this->timeFormat ? '['.date($this->timeFormat).']' : '') . '['.$level.'] '.$message;
        }

        $this->write($method, $message);
    }

    private function write(string $method, string $message): void
    {
        $style = self::$methodStyles[$method] ?? null;
        $message = $style ? "<$style>$message</$style>" : $message;
        if($this->wlnAborted()){
            $this->command->newLine();
        }
        $this->command->getOutput()->write($message);
        $this->checkWln(fn() => $this->command->newLine());
    }
}
