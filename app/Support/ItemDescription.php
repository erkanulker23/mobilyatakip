<?php

namespace App\Support;

class ItemDescription
{
    public static function lines(?string $description): array
    {
        if ($description === null || trim($description) === '') {
            return [];
        }

        $text = str_replace(["\r\n", "\r"], "\n", trim($description));
        $parts = preg_split('/\n+/', $text) ?: [];
        $lines = [];

        foreach ($parts as $part) {
            $line = self::cleanLine($part);
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    public static function cleanLine(string $line): string
    {
        $line = trim($line);
        $line = preg_replace('/^[-*•·]\s+/u', '', $line) ?? $line;
        $line = preg_replace('/^\d+[.)]\s+/u', '', $line) ?? $line;

        return trim($line);
    }

    public static function normalize(?string $description): ?string
    {
        $lines = self::lines($description);

        return $lines === [] ? null : implode("\n", $lines);
    }

    public static function fromInput(mixed $input): ?string
    {
        if (is_array($input)) {
            $lines = [];
            foreach ($input as $line) {
                $clean = self::cleanLine((string) $line);
                if ($clean !== '') {
                    $lines[] = $clean;
                }
            }

            return $lines === [] ? null : implode("\n", $lines);
        }

        if (! is_string($input)) {
            return null;
        }

        return self::normalize($input);
    }
}
