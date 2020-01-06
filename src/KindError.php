<?php
declare(strict_types=1);

namespace Hugger;


use Hugger\Template\Color;

abstract class KindError
{
    protected array $options = [];
    protected array $data = [];
    protected ?array $parsed = null;

    public function __construct(array $opts = [])
    {
        $this->options = $opts;
    }

    public function withError(
        int $errorCode,
        string $errorName,
        string $errorMessage,
        string $errorFile,
        int $errorLine,
        array $errorContext = []
    ): self {
        $error = clone $this;
        $error->data = [
            'errCode' => $errorCode,
            'errName' => $errorName,
            'errMessage' => $errorMessage,
            'errFile' => $errorFile,
            'errLine' => $errorLine,
            'errContext' => $errorContext,
        ];
        $error->parsed = null;
        $error->parse();

        return $error;
    }

    public function pattern(): ?string
    {
        return null;
    }

    protected function parse(): void
    {
        if (null !== $this->parsed || !($pattern = $this->pattern())) {
            $this->parsed = [];
            return;
        }

        preg_match($pattern, $this->data['errMessage'], $m);
        $this->parsed = array_filter($m, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);
    }

    public function data(): array
    {
        if (!$this->parsed) {
            $this->parse();
        }

        return $this->data + $this->parsed + [
            'name' => $this->name(),
            'relFile' => $this->file(),
            'main' => $this->main(),
            'trace' => $this->trace(),
            'found' => $this->found(),
            'expected' => $this->expected(),
            'hint' => $this->hint(),
        ];
    }

    //
    // PUBLIC API
    //

    abstract public function main(): Template;

    public function name(): Template
    {
        if (!empty($this->data['errContext']['throwable'])) {
            return Template::str(get_class($this->data['errContext']['throwable']));
        }

        return Template::str($this->data['errName']);
    }

    public function file(): Template
    {
        $file = $this->data['errFile'] ?? null;
        if ($file && !empty($this->options['dir'])) {
            $file = ltrim(
                str_replace($this->options['dir'], '', $file),
                DIRECTORY_SEPARATOR
            );
        }

        return Template::str(trim($file ?? '__unknown__'));
    }

    public function line(): Template
    {
        return Template::str((string) $this->data['errLine']);
    }

    public function trace(): Template
    {
        $errLine = (int) $this->data['errLine'];
        $lines = ErrorHandler::getCodeLines(
            trim($this->data['errFile']),
            $errLine
        );

        return Template::list(...array_map(
            static function ($k, $v) use ($errLine, $lines) {
                $line = count($lines) > 1 && $k === $errLine
                    ? '%d' . Color::red('|') : '%d|';
                return sprintf($line . '    %s', $k, $v);
            },
            array_keys($lines),
            array_values($lines)
        ));
    }

    abstract public function expected(): Template;

    abstract public function found(): Template;

    abstract public function hint(): Template;
}