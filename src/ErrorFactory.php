<?php
declare(strict_types=1);

namespace Hugger;


use Hugger\Type\Error;

class ErrorFactory
{
    private array $options = [];

    private array $map = [
        'Call to undefined function' => Error\CallToUndefinedFunction::class,
        'Undefined variable' => Error\UndefinedVariable::class,
        //'Typed property' => '',
        'Use of undefined constant' => Error\UseOfUndefinedConstant::class,
    ];

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function fromError(
        int $errorCode,
        string $errorName,
        string $errorMessage,
        string $errorFile,
        int $errorLine,
        array $errorContext = []
    ): KindError {
        foreach ($this->map as $start => $class) {
            if (0 === strpos($errorMessage, $start)) {
                return (new $class($this->options))
                    ->withError(...func_get_args());
            }
        }

        return (new Error($this->options))
            ->withError(...func_get_args());
    }
}
