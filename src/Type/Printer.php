<?php
declare(strict_types=1);

namespace Hugger\Type;


use Hugger\ErrorHandler;
use Hugger\KindError;
use Hugger\Template;
use Hugger\Template\Color;

class Printer
{
    public function print(KindError $err): void
    {
        echo $this->make($err);
    }

    private function make(KindError $err): string
    {
        $data = $err->data();
        $data = array_map(
            fn ($v) => $v instanceof Template ? $v->resolve($data) : $v,
            $data
        );

        return $this->template()->resolve($data);
    }

    private function template(): Template
    {
        return Template::list(
            '',
            Color::cyan(
                '-- %name$s'
                . ' ------------------------------------------------ '
                . '%relFile$s'
            ),
            '',
            '%main$s:',
            '',
            '%trace$s',
            '',
            '%expected$s',
            '',
            '%found$s',
            '',
            'Hint: %hint$s',
            '',
            '',
        );
    }
}
