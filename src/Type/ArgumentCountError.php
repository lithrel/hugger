<?php
declare(strict_types=1);

namespace Hugger\Type;

use Hugger\ErrorHandler;
use Hugger\KindError;
use Hugger\Template as Tpl;
use Hugger\Template\Indent as I;

class ArgumentCountError extends KindError
{
    public const INDENT = '    ';
    private array $params = [];

    /**
     * @return string
     */
    public function pattern(): string
    {
        return '#Too few arguments to function (?<calledFunc>.*), '
            . '(?<givenArgsCount>[\d]*) passed '
            . 'in (?<calledFile>.*)'
            . 'on line (?<calledLine>[\d]*) '
            . 'and(?<specifier>.*) (?<expectedArgsCount>[\d]*) expected#';
    }

    public function main(): Tpl
    {
        return Tpl::str(
            'The number of parameters you gave '
            . 'doesn\'t seem to match the definition'
        );
    }

    public function expected(): Tpl
    {
        return Tpl::list(
            'This function needs '
                . Tpl\Color::yellow('%expectedArgsCount$d')
                .' parameter:',
            '',
            I::indent(
                preg_replace(
                    '#\((.*)\)#',
                    '(' . Tpl\Color::yellow('$1') . ')',
                    $this->parsed['funcSignature']
                )
            )
        );
    }

    public function found(): Tpl
    {
        $found = (int) $this->parsed['givenArgsCount'] === 0
            ? 'But you gave ' . Tpl\Color::red('none') . ':'
            : ($this->params['argsDiff'] > 0
                ? 'But you only gave %givenArgsCount$'
                : 'But you gave %givenArgsCount$'
            );

        return Tpl::list(
            $found,
            '',
            I::indent(str_replace(
                '()',
                '(' . Tpl\Color::red('█') . ')',
                $this->parsed['calledFunc']
            ))
        );
    }

    public function trace(): Tpl
    {
        $errLine = (int) $this->parsed['calledLine'];
        $lines = ErrorHandler::getCodeLines(
            trim($this->parsed['calledFile']),
            $errLine,
            3
        );

        return Tpl::list(...array_map(
            static function ($k, $v) use ($errLine, $lines) {
                $line = count($lines) > 1 && $k === $errLine
                    ? '%d' . Tpl\Color::red('|') : '%d|';
                return sprintf($line . '    %s', $k, $v);
            },
            array_keys($lines),
            array_values($lines)
        ));
    }

    public function hint(): Tpl
    {
        return Tpl::str('Add a parameter to this call');
    }

    //
    // Additional data
    //

    protected function parse(): void
    {
        parent::parse();
        $this->parsed['funcSignature'] = $this->parseFuncSignature();
        $this->params = [
            'argsDiff' => (int) $this->parsed['expectedArgsCount']
                - (int) $this->parsed['givenArgsCount']
        ];
    }

    private function parseFuncSignature(): string
    {
        var_dump($this->parsed['calledFunc'], $this->parsed);
        $funcName = trim($this->parsed['calledFunc'], '()');
        [$funcSignature] = explode(
            '{',
            ErrorHandler::getFunctionCode($funcName),
            2
        );
        return trim($funcSignature);
    }
}
