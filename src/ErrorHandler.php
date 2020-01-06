<?php
declare(strict_types=1);

namespace Hugger;

use Hugger\Type\ArgumentCountError;
use Hugger\Type\Error;
use Hugger\Type\Printer;

class ErrorHandler
{
    public static function register(): self
    {
        $handler = new static();
        $handler
            ->registerExceptionHandler()
            ->registerFatalHandler()
            ->registerErrorHandler()
        ;
        return $handler;
    }

    public function registerExceptionHandler($levelMap = [], $callPrevious = true): self
    {
        $prev = set_exception_handler([$this, 'handleException']);
        return $this;
    }

    public function registerErrorHandler(array $levelMap = [], $callPrevious = true, $errorTypes = -1, $handleOnlyReportedErrors = true): self
    {
        $prev = set_error_handler([$this, 'handleError'], $errorTypes);
        return $this;
    }

    public function registerFatalHandler($level = null, int $reservedMemorySize = 20): self
    {
        register_shutdown_function([$this, 'handleFatalError']);
        // $this->reservedMemory = str_repeat(' ', 1024 * $reservedMemorySize);
        return $this;
    }

    public function handleException(\Throwable $e): void
    {
        $this->beKind(
            1,
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            ['from' => 'Exception', 'throwable' => $e]
        );
    }

    public function handleFatalError(): void
    {
        $error = error_get_last();
        if (null === $error) {
            return;
        }

        $errorData = array_values($error) + [['from' => 'FatalError']];
        $this->handleError(...$errorData);
    }

    public function handleError(
        int $errno,
        string $errstr,
        string $errfile,
        int $errline,
        array $errcontext = []
    ): void {
        [$errName] = explode(':', $errstr, 2);
        $errcontext['from'] = 'Error';

        $this->beKind(
            $errno,
            str_replace('Uncaught ', '', $errName),
            $errstr,
            $errfile,
            $errline,
            $errcontext
        );
    }

    public function beKind(
        int $errorCode,
        string $errorName,
        string $errorMessage,
        string $errorFile,
        int $errorLine,
        array $errorContext = []
    ): void {
        $errorFactory = new ErrorFactory(['dir' => ROOT_DIR]);

        switch ($errorName) {
            case 'ArgumentCountError':
                $err = (new ArgumentCountError(['dir' => ROOT_DIR]))
                    ->withError(...func_get_args());
                break;
            case 'Error':
                $err = $errorFactory->fromError(...func_get_args());
                break;
            default:
                $err = $errorFactory->fromError(...func_get_args());
                break;
                //$err = $errorMessage;
                //var_dump(' -- oups', $errorName, $errorMessage, $errorFile, $errorLine, $errorContext['from']);
                //debug_print_backtrace();
                //exit(255);
        }

        (new Printer())->print($err);
        die();
    }

    public static function getFunctionCode(string $funcName): string
    {
        $func = false === strpos($funcName, '::')
            ? new \ReflectionFunction($funcName)
            : new \ReflectionMethod(...explode('::', $funcName));

        $filename = $func->getFileName();
        $start_line = $func->getStartLine() - 1; // it's actually - 1, otherwise you wont get the function() block
        $end_line = $func->getEndLine();
        $length = $end_line - $start_line;

        $source = file($filename);
        return implode('', array_slice($source, $start_line, $length));
    }

    public static function getCodeLines(
        string $filename,
        int $line,
        int $nbOfLines = 1
    ): array {
        $source = file($filename, \FILE_IGNORE_NEW_LINES);
        $lineInSource = $line - 1;
        if (empty($source[$lineInSource])) {
            return [];
        }

        $start = $nbOfLines === 1
            ? $lineInSource : max($lineInSource - max($nbOfLines%2, 1), 0);
        $lines = array_slice($source, $start, $nbOfLines, true);
        $nbOfLines = max(1, $nbOfLines);

        $linesIndex = array_map(
            fn ($i) => $i+1,
            array_keys($lines)
        );

        /*echo print_r([
            'line' => $line,
            'lineInSource' => $lineInSource,
            'nbOfLines' => $nbOfLines,
            'start' => $start,
            'lines' => $lines,
            'linesIndex' => implode(',', $linesIndex),
            'source' => $source,
        ], true);
        die();*/

        return array_combine($linesIndex, array_values($lines));
    }
}
