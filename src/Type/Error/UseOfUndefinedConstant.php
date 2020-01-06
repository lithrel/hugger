<?php
declare(strict_types=1);

namespace Hugger\Type\Error;

use Hugger\Template as Tpl;
use Hugger\Type\Error;
use Hugger\Template\Indent as I;

class UseOfUndefinedConstant extends Error
{
    private int $maxPropositions = 3;

    public function pattern(): ?string
    {
        return '#Use of undefined constant (?<calledVariable>.*) '
            . '- assumed (?<assumedValue>.*)#';
    }

    public function expected(): Tpl
    {
        return Tpl::list(
            'I was looking for a constant declaration like this:',
            '',
            '    define(\'%calledVariable$s\', \'foo\');'
        );
    }

    public function found(): Tpl
    {
        return Tpl::str('But I couldn\'t find it.');
    }

    public function hint(): Tpl
    {
        // fuzzy search in existing
        $called = trim($this->parsed['calledVariable'], '()');
        $calledLen = strlen($called);
        $constants = array_keys(\get_defined_constants());

        $distMap = array_map(
            fn ($f) => [$f, levenshtein($f, $called)],
            $constants
        );
        /*$distMap += array_map(
            fn ($f) => [$f, levenshtein($f, $called)],
            array_merge($constants['core'], $constants['pcre'])
        );*/

        usort(
            $distMap,
            fn ($a, $b) => $a[1] <=> $b[1]
        );

        return Tpl::list(
            'There are a few possibilities:',
            I::indent('- You forgot a $ sign in front of your variable name,'),
            I::indent('- Maybe you were looking for one of those constants '
                . '(constant names are case-sensitive),'),
            ...array_map(
                fn ($f) => I::indent('    ' . $f),
                array_slice(array_column($distMap, 0), 0, $this->maxPropositions)
            ),
        );
    }
}