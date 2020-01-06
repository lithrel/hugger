<?php
declare(strict_types=1);

namespace Hugger\Type\Error;

use Hugger\Template;
use Hugger\Type\Error;

class UndefinedVariable extends Error
{
    public function pattern(): string
    {
        return '#Undefined variable: (?<variableName>.*)#';
    }

    public function main(): Template
    {
        return Template::str('A variable you\'re using is missing');
    }

    public function expected(): Template
    {
        return Template::list(
            'I was looking for a '
                . Template\Color::yellow('definition of $%variableName$s')
                . ' before this call, for example:',
            '',
            Template\Indent::indent('$%variableName$s = \'foo\';')
        );
    }

    public function found(): Template
    {
        $var = '$' . $this->parsed['variableName'];
        $trace = $this->trace()->resolve($this->data);
        return Template::list(
            'But this variable is undefined:',
            '',
            Template\Indent::indent(
                str_replace($var, Template\Color::red($var), $trace)
            )
        );
    }

    public function hint(): Template
    {
        return Template::list(
            'There are a few possibilities:',
            Template\Indent::indent('- You forgot to declare this variable,'),
            Template\Indent::indent('- You mistyped its name (variable names are case-sensitive !),'),
            Template\Indent::indent(
                '- This variable is not available in this scope (think about '
                . '"use ($%variableName$s)" for an anonymous function).'
            )
        );
    }
}
