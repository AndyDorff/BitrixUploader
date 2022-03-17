<?php


namespace Aniart\BitrixUploader\Loggers;


trait WithoutNewLineModeTrait
{
    private static int $WLN_DISABLED = 0;
    private static int $WLN_ENABLED = 1;
    private static int $WLN_IN_PROCESS = 2;
    private static int $WLN_ABORT = 4;

    private int $wlnMode = 0;

    public function wln(bool $wlnMode = true): static
    {
        $this->wlnMode = $wlnMode ? self::$WLN_ENABLED : ($this->wlnMode | self::$WLN_ABORT);

        return $this;
    }

    private function wlnAborted(): bool
    {
        return ($this->wlnMode & self::$WLN_ABORT) === self::$WLN_ABORT;
    }

    private function wlnInProcess(): bool
    {
        return ($this->wlnMode & self::$WLN_IN_PROCESS) === self::$WLN_IN_PROCESS;
    }

    private function checkWln(callable $fn): void
    {
        if($this->wlnMode === self::$WLN_ENABLED){
            $this->wlnMode = self::$WLN_IN_PROCESS;
        } else {
            call_user_func($fn);
            if($this->wlnMode === self::$WLN_IN_PROCESS){
                $this->wlnMode = self::$WLN_DISABLED;
            }
        }
    }
}