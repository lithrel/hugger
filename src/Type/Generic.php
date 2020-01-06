<?php
declare(strict_types=1);

namespace Hugger\Type;


use Hugger\KindError;

class Generic extends KindError
{
    public function hint(): string
    {
        return 'Fix this error maybe ?';
    }
}