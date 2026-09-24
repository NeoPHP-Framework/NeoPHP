<?php

declare(strict_types=1);

namespace NeoPHP\Package\Yaml\Parser;

use NeoPHP\Package\Yaml\Exception\ParseException;

class Parser
{
    private array $lines = [];

    private int $cursor = 0;

    public function parse(string $input): mixed
    {
        $input = str_replace(["\r\n", "\r"], "\n", $input);

        if (str_starts_with($input, "\xEF\xBB\xBF")) {
            $input = substr($input, 3);
        }

        $this->lines = explode("\n", $input);
        $this->cursor = 0;

        $this->skipDocumentMarker();

        $next = $this->nextSignificantLine();

        if ($next === null) {
            return null;
        }

        $value = $this->parseBlock($this->indentOf($this->lines[$next]));

        $remaining = $this->nextSignificantLine();

        if ($remaining !== null) {
            throw new ParseException('Unexpected content (check the indentation)', $remaining + 1, $this->lines[$remaining]);
        }

        return $value;
    }

    private function skipDocumentMarker(): void
    {
        $next = $this->nextSignificantLine();

        if ($next !== null && rtrim($this->lines[$next]) === '---') {
            $this->cursor = $next + 1;
        }
    }

    private function parseBlock(int $indent): mixed
    {
        $index = $this->nextSignificantLine();

        if ($index === null) {
            return null;
        }

        $content = $this->contentOf($index);

        if ($this->isSequenceItem($content)) {
            return $this->parseSequence($indent);
        }

        if ($this->splitKey($content, $index) !== null) {
            return $this->parseMapping($indent);
        }

        $this->cursor = $index + 1;

        return $this->parseInlineValue($content, $index, $indent);
    }

    private function parseMapping(int $indent): array
    {
        $result = [];

        while (($index = $this->nextSignificantLine()) !== null) {
            $lineIndent = $this->indentOf($this->lines[$index]);

            if ($lineIndent < $indent) {
                break;
            }

            if ($lineIndent > $indent) {
                throw new ParseException('Unexpected indentation', $index + 1, $this->lines[$index]);
            }

            $content = $this->contentOf($index);
            $pair = $this->splitKey($content, $index);

            if ($pair === null) {
                if ($this->isSequenceItem($content)) {
                    break;
                }

                throw new ParseException('Expected a "key: value" pair', $index + 1, $this->lines[$index]);
            }

            [$key, $rest] = $pair;
            $this->cursor = $index + 1;

            if ($rest === '') {
                $result[$key] = $this->parseChildBlock($indent, true);
                continue;
            }

            $result[$key] = $this->parseInlineValue($rest, $index, $indent);
        }

        return $result;
    }

    private function parseSequence(int $indent): array
    {
        $result = [];

        while (($index = $this->nextSignificantLine()) !== null) {
            $line = $this->lines[$index];
            $lineIndent = $this->indentOf($line);

            if ($lineIndent < $indent) {
                break;
            }

            if ($lineIndent > $indent) {
                throw new ParseException('Unexpected indentation', $index + 1, $line);
            }

            $content = $this->contentOf($index);

            if (!$this->isSequenceItem($content)) {
                break;
            }

            $rest = ltrim(substr($content, 1));

            if ($rest === '') {
                $this->cursor = $index + 1;
                $result[] = $this->parseChildBlock($indent, false);
                continue;
            }

            if ($this->splitKey($rest, $index) !== null && !$this->isFlowStart($rest)) {
                $column = $lineIndent + (strlen(ltrim($line)) - strlen(ltrim(substr(ltrim($line), 1))));
                $this->lines[$index] = str_repeat(' ', $column) . ltrim(substr(ltrim($line), 1));
                $this->cursor = $index;
                $result[] = $this->parseMapping($column);
                continue;
            }

            $this->cursor = $index + 1;
            $result[] = $this->parseInlineValue($rest, $index, $indent);
        }

        return $result;
    }

    private function parseChildBlock(int $parentIndent, bool $allowSameIndentSequence): mixed
    {
        $index = $this->nextSignificantLine();

        if ($index === null) {
            return null;
        }

        $childIndent = $this->indentOf($this->lines[$index]);

        if ($childIndent > $parentIndent) {
            return $this->parseBlock($childIndent);
        }

        if ($allowSameIndentSequence && $childIndent === $parentIndent && $this->isSequenceItem($this->contentOf($index))) {
            return $this->parseSequence($childIndent);
        }

        return null;
    }

    private function parseInlineValue(string $value, int $index, int $indent): mixed
    {
        if (preg_match('/^([|>])([+-]?)\d*$/', $value, $m) === 1) {
            return $this->parseBlockScalar($m[1] === '>', $m[2], $indent);
        }

        if ($this->isFlowStart($value)) {
            $value = $this->collectFlow($value, $index);
            $position = 0;
            $result = $this->parseFlowValue($value, $position, $index);
            $this->skipSpaces($value, $position);

            if ($position !== strlen($value)) {
                throw new ParseException('Unexpected characters after flow collection', $index + 1, $this->lines[$index]);
            }

            return $result;
        }

        return $this->parseScalar($value, $index);
    }

    private function parseBlockScalar(bool $folded, string $chomping, int $parentIndent): string
    {
        $collected = [];
        $blockIndent = null;

        while ($this->cursor < count($this->lines)) {
            $line = $this->lines[$this->cursor];

            if (trim($line) === '') {
                $collected[] = '';
                $this->cursor++;
                continue;
            }

            $lineIndent = $this->indentOf($line);

            if ($lineIndent <= $parentIndent) {
                break;
            }

            $blockIndent ??= $lineIndent;

            if ($lineIndent < $blockIndent) {
                break;
            }

            $collected[] = substr($line, $blockIndent);
            $this->cursor++;
        }

        $trailing = 0;
        while ($collected !== [] && end($collected) === '') {
            array_pop($collected);
            $trailing++;
        }

        if ($folded) {
            $text = '';
            foreach ($collected as $i => $line) {
                if ($i === 0) {
                    $text = $line;
                } elseif ($line === '' || str_starts_with($line, ' ')) {
                    $text .= "\n" . $line;
                } else {
                    $text .= (str_ends_with($text, "\n") || $text === '' ? '' : ' ') . $line;
                }
            }
        } else {
            $text = implode("\n", $collected);
        }

        return match ($chomping) {
            '-' => $text,
            '+' => $text . str_repeat("\n", $trailing + 1),
            default => $text === '' ? '' : $text . "\n",
        };
    }

    private function collectFlow(string $value, int $index): string
    {
        while (!$this->isFlowBalanced($value)) {
            if ($this->cursor >= count($this->lines)) {
                throw new ParseException('Unclosed flow collection', $index + 1, $this->lines[$index]);
            }

            $value .= ' ' . trim($this->stripComment($this->lines[$this->cursor]));
            $this->cursor++;
        }

        return $value;
    }

    private function isFlowBalanced(string $value): bool
    {
        $depth = 0;
        $quote = null;
        $length = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $char = $value[$i];

            if ($quote !== null) {
                if ($char === '\\' && $quote === '"') {
                    $i++;
                } elseif ($char === $quote) {
                    $quote = null;
                }
                continue;
            }

            match ($char) {
                '"', "'" => $quote = $char,
                '[', '{' => $depth++,
                ']', '}' => $depth--,
                default => null,
            };
        }

        return $depth <= 0;
    }

    private function parseFlowValue(string $input, int &$position, int $line): mixed
    {
        $this->skipSpaces($input, $position);
        $char = $input[$position] ?? '';

        if ($char === '[') {
            $position++;
            $result = [];
            $this->skipSpaces($input, $position);

            if (($input[$position] ?? '') === ']') {
                $position++;

                return $result;
            }

            while (true) {
                $result[] = $this->parseFlowValue($input, $position, $line);
                $this->skipSpaces($input, $position);
                $next = $input[$position++] ?? '';

                if ($next === ']') {
                    return $result;
                }

                if ($next !== ',') {
                    throw new ParseException('Expected "," or "]" in flow sequence', $line + 1, $input);
                }

                $this->skipSpaces($input, $position);

                if (($input[$position] ?? '') === ']') {
                    $position++;

                    return $result;
                }
            }
        }

        if ($char === '{') {
            $position++;
            $result = [];
            $this->skipSpaces($input, $position);

            if (($input[$position] ?? '') === '}') {
                $position++;

                return $result;
            }

            while (true) {
                $this->skipSpaces($input, $position);
                $key = $this->readFlowToken($input, $position, true);
                $this->skipSpaces($input, $position);

                if (($input[$position] ?? '') !== ':') {
                    throw new ParseException('Expected ":" in flow mapping', $line + 1, $input);
                }

                $position++;
                $key = $this->parseScalar($key, $line);
                $result[is_bool($key) ? (int) $key : (string) $key] = $this->parseFlowValue($input, $position, $line);
                $this->skipSpaces($input, $position);
                $next = $input[$position++] ?? '';

                if ($next === '}') {
                    return $result;
                }

                if ($next !== ',') {
                    throw new ParseException('Expected "," or "}" in flow mapping', $line + 1, $input);
                }

                $this->skipSpaces($input, $position);

                if (($input[$position] ?? '') === '}') {
                    $position++;

                    return $result;
                }
            }
        }

        return $this->parseScalar($this->readFlowToken($input, $position, false), $line);
    }

    private function readFlowToken(string $input, int &$position, bool $isKey): string
    {
        $start = $position;
        $char = $input[$position] ?? '';

        if ($char === '"' || $char === "'") {
            $position++;
            $length = strlen($input);

            while ($position < $length) {
                if ($char === '"' && $input[$position] === '\\') {
                    $position += 2;
                    continue;
                }

                if ($input[$position] === $char) {
                    if ($char === "'" && ($input[$position + 1] ?? '') === "'") {
                        $position += 2;
                        continue;
                    }

                    $position++;
                    break;
                }

                $position++;
            }

            return substr($input, $start, $position - $start);
        }

        $length = strlen($input);

        while ($position < $length) {
            $current = $input[$position];

            if ($current === ',' || $current === ']' || $current === '}') {
                break;
            }

            if ($current === ':' && $isKey) {
                break;
            }

            $position++;
        }

        return trim(substr($input, $start, $position - $start));
    }

    private function skipSpaces(string $input, int &$position): void
    {
        $length = strlen($input);

        while ($position < $length && ($input[$position] === ' ' || $input[$position] === "\t")) {
            $position++;
        }
    }

    private function parseScalar(string $value, int $line): mixed
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $first = $value[0];

        if ($first === '"') {
            if (strlen($value) < 2 || !str_ends_with($value, '"')) {
                throw new ParseException('Unclosed double-quoted string', $line + 1, $value);
            }

            return $this->unescapeDoubleQuoted(substr($value, 1, -1));
        }

        if ($first === "'") {
            if (strlen($value) < 2 || !str_ends_with($value, "'")) {
                throw new ParseException('Unclosed single-quoted string', $line + 1, $value);
            }

            return str_replace("''", "'", substr($value, 1, -1));
        }

        $lower = strtolower($value);

        return match (true) {
            $lower === '~' || $lower === 'null' => null,
            $lower === 'true' => true,
            $lower === 'false' => false,
            $lower === '.inf' || $lower === '+.inf' => INF,
            $lower === '-.inf' => -INF,
            $lower === '.nan' => NAN,
            preg_match('/^[-+]?(0|[1-9]\d*)$/', $value) === 1 => $this->toInt($value),
            preg_match('/^0x[0-9a-fA-F]+$/', $value) === 1 => (int) hexdec($value),
            preg_match('/^0o[0-7]+$/', $value) === 1 => (int) octdec(substr($value, 2)),
            preg_match('/^[-+]?(\d+\.\d*|\.\d+|\d+)([eE][-+]?\d+)?$/', $value) === 1 => (float) $value,
            default => $value,
        };
    }

    private function toInt(string $value): int|float
    {
        $int = filter_var($value, FILTER_VALIDATE_INT);

        return $int === false ? (float) $value : $int;
    }

    private function unescapeDoubleQuoted(string $value): string
    {
        return (string) preg_replace_callback(
            '/\\\\(u[0-9a-fA-F]{4}|x[0-9a-fA-F]{2}|.)/',
            static function (array $m): string {
                $sequence = $m[1];

                if ($sequence[0] === 'u') {
                    return mb_chr((int) hexdec(substr($sequence, 1)), 'UTF-8');
                }

                if ($sequence[0] === 'x' && strlen($sequence) === 3) {
                    return chr((int) hexdec(substr($sequence, 1)));
                }

                return match ($sequence) {
                    'n' => "\n",
                    't' => "\t",
                    'r' => "\r",
                    '0' => "\0",
                    'e' => "\e",
                    ' ' => ' ',
                    '/' => '/',
                    '"' => '"',
                    '\\' => '\\',
                    default => '\\' . $sequence,
                };
            },
            $value,
        );
    }

    private function splitKey(string $content, int $index): ?array
    {
        if ($content === '' || $this->isFlowStart($content)) {
            return null;
        }

        $first = $content[0];

        if ($first === '"' || $first === "'") {
            $end = $this->findClosingQuote($content, $first);

            if ($end === null) {
                return null;
            }

            $after = ltrim(substr($content, $end + 1));

            if (!str_starts_with($after, ':')) {
                return null;
            }

            $rest = substr($after, 1);

            if ($rest !== '' && !ctype_space($rest[0])) {
                return null;
            }

            return [(string) $this->parseScalar(substr($content, 0, $end + 1), $index), trim($rest)];
        }

        $length = strlen($content);

        for ($i = 0; $i < $length; $i++) {
            if ($content[$i] === ':' && ($i + 1 === $length || $content[$i + 1] === ' ' || $content[$i + 1] === "\t")) {
                $key = rtrim(substr($content, 0, $i));

                if ($key === '' || str_starts_with($key, '- ')) {
                    return null;
                }

                return [$key, trim(substr($content, $i + 1))];
            }
        }

        return null;
    }

    private function findClosingQuote(string $content, string $quote): ?int
    {
        $length = strlen($content);

        for ($i = 1; $i < $length; $i++) {
            if ($quote === '"' && $content[$i] === '\\') {
                $i++;
                continue;
            }

            if ($content[$i] === $quote) {
                if ($quote === "'" && ($content[$i + 1] ?? '') === "'") {
                    $i++;
                    continue;
                }

                return $i;
            }
        }

        return null;
    }

    private function isSequenceItem(string $content): bool
    {
        return $content === '-' || str_starts_with($content, '- ') || str_starts_with($content, "-\t");
    }

    private function isFlowStart(string $content): bool
    {
        return $content !== '' && ($content[0] === '[' || $content[0] === '{');
    }

    private function nextSignificantLine(): ?int
    {
        $count = count($this->lines);

        for ($i = $this->cursor; $i < $count; $i++) {
            $content = trim($this->stripComment($this->lines[$i]));

            if ($content !== '') {
                if (str_contains(substr($this->lines[$i], 0, $this->indentOf($this->lines[$i])), "\t")) {
                    throw new ParseException('Tabs are not allowed for indentation', $i + 1, $this->lines[$i]);
                }

                return $i;
            }
        }

        $this->cursor = $count;

        return null;
    }

    private function contentOf(int $index): string
    {
        return trim($this->stripComment($this->lines[$index]));
    }

    private function indentOf(string $line): int
    {
        return strlen($line) - strlen(ltrim($line, " \t"));
    }

    private function stripComment(string $line): string
    {
        if (!str_contains($line, '#')) {
            return $line;
        }

        $quote = null;
        $length = strlen($line);

        for ($i = 0; $i < $length; $i++) {
            $char = $line[$i];

            if ($quote !== null) {
                if ($quote === '"' && $char === '\\') {
                    $i++;
                } elseif ($char === $quote) {
                    $quote = null;
                }
                continue;
            }

            if (($char === '"' || $char === "'") && ($i === 0 || in_array($line[$i - 1], [' ', "\t", '[', '{', ',', ':', '-'], true))) {
                $quote = $char;
                continue;
            }

            if ($char === '#' && ($i === 0 || $line[$i - 1] === ' ' || $line[$i - 1] === "\t")) {
                return substr($line, 0, $i);
            }
        }

        return $line;
    }
}