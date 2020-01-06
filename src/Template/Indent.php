<?php
declare(strict_types=1);

namespace Hugger\Template;


class Indent
{
    private static int $defaultCount = 4;
    private static string $defaultChar = ' ';

    public static function indent(string $str, ?string $sp = null): string
    {
        $sp ??= str_repeat(self::$defaultChar, self::$defaultCount);
        return $sp . $str;
    }
}
