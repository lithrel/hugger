<?php
declare(strict_types=1);

namespace Hugger\Template;

/**
 * @method static string black(string $str)
 * @method static string red(string $str)
 * @method static string green(string $str)
 * @method static string yellow(string $str)
 * @method static string blue(string $str)
 * @method static string magenta(string $str)
 * @method static string cyan(string $str)
 * @method static string white(string $str)
 */
class Color
{
    public static array $fg = [
        'black' => '30',
        'red' => '31',
        'green' => '32',
        'yellow' => '33',
        'blue' => '34',
        'magenta' => '35',
        'cyan' => '36',
        'white' => '37',
    ];

    public function resolve(string $str): string
    {
        return $this->resolveCli($str);
    }

    private function resolveCli(string $str): string
    {
        foreach (self::colors() as $c) {
            $tags = ['<' . $c . '>', '</' . $c . '>'];
            $replace = ["\033[1;" . self::$fg[$c] . 'm', "\033[0m"];
            $str = str_replace( $tags, $replace, $str);
        }
        return $str;
    }

    public static function __callStatic($name, $arguments)
    {
        if (!in_array($name, self::colors(), true)) {
            throw new \Exception(sprintf('Color %s unknown', $name));
        }

        return Tag::tag($name, ...$arguments);
    }

    public static function colors(): array
    {
        return array_keys(self::$fg);
    }
}
