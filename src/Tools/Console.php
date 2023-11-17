<?php

namespace Src\Tools;

class Console
{
    const COLOR_YELLOW = '0;33';
    const COLOR_GREEN = '0;32';
    const COLOR_RED = '0;31';

    public static function write(string $text, string $color = '', string $eol = PHP_EOL): void
    {
        if (empty($color)) {
            $data = $text . $eol;
        } else {
            $data = "\033[" . $color . "m" . $text . "\033[0m" . $eol;
        }

        fwrite(STDOUT, $data);
    }
}