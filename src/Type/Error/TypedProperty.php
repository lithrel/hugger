<?php
declare(strict_types=1);

namespace Hugger\Type\Error;


use Hugger\Type\Error;

class TypedProperty extends Error
{
    public function __construct(array $opts = [])
    {
        parent::__construct($opts);
    }
}