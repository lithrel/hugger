<?php
declare(strict_types=1);

namespace Hugger;

use Hugger\Template\Color;

class Template
{
    /**
     * @var string[]
     */
    private array $template;

    private function __construct(string ...$str)
    {
        $this->template = is_array($str) ? $str : [$str];
    }

    public function resolve(array $data): string
    {
        unset($data['errContext']);
        $tpl = implode("\n", array_map(
            fn ($str) => self::sprintfn($str, $data),
            $this->template
        ));
        return (new Color())->resolve($tpl);
    }

    public static function str(string $str): self
    {
        return new self($str);
    }

    public static function list(string ...$str): self
    {
        return new self(...$str);
    }

    public static function urlencode(string $url): string
    {
        return str_replace('%', '%%', urlencode($url));
    }

    //
    // Resolve
    //

    public static function sprintfn(string $tpl, array $args = []): string
    {
        $namedArgs = array_filter(
            $args,
            fn ($key) => !is_int($key), ARRAY_FILTER_USE_KEY
        );

        $namedArgs = array_map(
            fn ($v) => is_array($v) ? implode("\n", $v) : $v,
            $namedArgs
        );

        $i = 1;
        foreach ($namedArgs as $key => $val) {
            $tpl = str_replace(
                '%' . $key . '$',
                '%' . $i . '$',
                $tpl
            );
            $i++;
        }

        return vsprintf($tpl, array_values($namedArgs));
    }
}
