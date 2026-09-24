<?php

declare(strict_types=1);

namespace NeoPHP\Process\Console\IO;

class Output
{
    public const STYLES = [
        'info' => '32',
        'success' => '1;32',
        'comment' => '33',
        'error' => '1;31',
        'title' => '1;35',
        'muted' => '2',
    ];

    protected mixed $stream;

    protected bool $decorated;

    public function __construct(mixed $stream = null, ?bool $decorated = null)
    {
        $this->stream = $stream ?? (defined('STDOUT') ? STDOUT : fopen('php://output', 'w'));
        $this->decorated = $decorated ?? $this->detectColors();
    }

    public function write(string $message): void
    {
        fwrite($this->stream, $this->format($message));
    }

    public function writeln(string $message = ''): void
    {
        $this->write($message . PHP_EOL);
    }

    public function table(array $headers, array $rows): void
    {
        $widths = array_map('mb_strlen', $headers);

        foreach ($rows as $row) {
            foreach (array_values($row) as $index => $cell) {
                $widths[$index] = max($widths[$index] ?? 0, mb_strlen((string) $cell));
            }
        }

        $line = '+' . implode('+', array_map(static fn (int $width): string => str_repeat('-', $width + 2), $widths)) . '+';
        $render = static function (array $cells) use ($widths): string {
            $output = '|';

            foreach ($widths as $index => $width) {
                $cell = (string) ($cells[$index] ?? '');
                $output .= ' ' . $cell . str_repeat(' ', $width - mb_strlen($cell)) . ' |';
            }

            return $output;
        };

        $this->writeln($line);
        $this->writeln($render(array_values($headers)));
        $this->writeln($line);

        foreach ($rows as $row) {
            $this->writeln($render(array_values($row)));
        }

        $this->writeln($line);
    }

    protected function format(string $message): string
    {
        return (string) preg_replace_callback(
            '#<(' . implode('|', array_keys(self::STYLES)) . ')>(.*?)</\1>#s',
            fn (array $m): string => $this->decorated ? "\033[" . self::STYLES[$m[1]] . 'm' . $m[2] . "\033[0m" : $m[2],
            $message,
        );
    }

    protected function detectColors(): bool
    {
        if (getenv('NO_COLOR') !== false || !is_resource($this->stream)) {
            return false;
        }

        if (DIRECTORY_SEPARATOR === '\\' && function_exists('sapi_windows_vt100_support')) {
            return @sapi_windows_vt100_support($this->stream, true);
        }

        return function_exists('stream_isatty') && @stream_isatty($this->stream);
    }
}