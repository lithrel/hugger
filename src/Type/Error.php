<?php
declare(strict_types=1);

namespace Hugger\Type;

use Hugger\KindError;
use Hugger\Template as Tpl;
use Hugger\Template\Indent as I;

class Error extends KindError
{
    /**
     * @var string|string[]
     */
    protected array $main = [
        'An error has occured',
        '" %errMessage$s "'
    ];

    protected string $expected = 'This is an unhandled error, '
        . 'I have no tips for this.';

    /**
     * @return Tpl
     */
    public function name(): Tpl
    {
        $class = explode('\\', static::class);
        $class = end($class);
        if ($class === 'Error') {
            return parent::name();
        }

        return Tpl::str($class);
    }

    public function main(): Tpl
    {
        return is_array($this->main)
            ? Tpl::list(...$this->main)
            : Tpl::str($this->main);
    }

    public function hint(): Tpl
    {
        $urls = [
            'https://stackoverflow.com/search?q=' . Tpl::urlencode(
                Tpl::str('%errMessage$s [php]')->resolve($this->data)),
            'https://duckduckgo.com/?q=' . Tpl::urlencode(
                Tpl::str('%errMessage$s php')->resolve($this->data)),
            'https://www.php.net/search.php?show=all&pattern=' . Tpl::urlencode(
                Tpl::str('%errMessage$s')->resolve($this->data))
        ];

        $list = array_merge(
            ['I have no specific insight on this error, '
                . 'but maybe you can try these links:', ''],
            array_map(fn ($u) => substr(I::indent('- ' . $u), 0, 120), $urls),
            [''],
            [$this->stack()->resolve([])]
        );

        return Tpl::list(...$list);
    }

    public function expected(): Tpl
    {
        return Tpl::str($this->expected);
    }

    public function found(): Tpl
    {
        return Tpl::str(I::indent('¯\\_(ツ)_/¯'));
    }

    private function stack(): Tpl
    {
        $e = $this->data['errContext']['throwable'] ?? null;
        $trace = $e instanceof \Throwable
            ? $e->getTraceAsString()
            : implode("\n", array_map(
                fn ($t) => sprintf('%s(%s) %s',
                    $t['file'] ?? '', $t['line'] ?? '', $t['function'].'()'),
                debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
            ));

        return Tpl::list(...array_merge(
            [I::indent('And because I can\'t help, here is a stacktrace:'), ''],
            array_map(
                fn ($s) => I::indent(str_replace(ROOT_DIR, '', $s)),
                explode("\n", $trace)
            )
        ));
    }
}
