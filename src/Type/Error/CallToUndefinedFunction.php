<?php
declare(strict_types=1);

namespace Hugger\Type\Error;


use Hugger\Template;
use Hugger\Type\Error;

class CallToUndefinedFunction extends Error
{
    private int $maxPropositions = 3;

    protected string $main = 'I couldn\'t find this function in your code';

    public function pattern(): string
    {
        return '#Call to undefined function (?<calledFunc>.*)#';
    }

    public function expected(): Template
    {
        return Template::list(
            'I was looking for a function declaration like this:',
            '',
            '    function %calledFunc$s: void { echo "my function !"; }'
        );
    }

    public function found(): Template
    {
        return Template::str('But I found nothing relevant.');
    }

    public function hint(): Template
    {
        // fuzzy search in existing
        $called = trim($this->parsed['calledFunc'], '()');
        $functions = \get_defined_functions();

        $distMap = array_map(
            fn ($f) => [$f, levenshtein($f, $called)],
            $functions['user']
        );
        $distMap += array_map(
            fn ($f) => [$f, levenshtein($f, $called)],
            $functions['internal']
        );

        usort(
            $distMap,
            fn ($a, $b) => $a[1] <=> $b[1]
        );

        return Template::list(
            'Maybe you were looking for one of those functions ?',
            ...array_map(
                fn ($f) => '    ' . $f,
                array_slice(array_column($distMap, 0), 0, $this->maxPropositions)
            )
        );
    }
}