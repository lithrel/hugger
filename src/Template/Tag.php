<?php
declare(strict_types=1);

namespace Hugger\Template;


class Tag
{
    public static function tag(string $tag, string $str = ''): string
    {
        return sprintf('<%s>%s</%s>', $tag, $str, $tag);
    }
}
