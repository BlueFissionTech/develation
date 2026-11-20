<?php

namespace BlueFission\Cli;

/**
 * Convert one authored command line to argv without invoking a shell.
 * This is not a command dispatcher or a natural-language interpreter.
 */
final class ArgvTokenizer
{
    /**
     * @param array<string> $allowedCommands Exact command names supplied by the caller.
     * @return array<string> Command plus arguments for Args::parse().
     * @throws \InvalidArgumentException On an empty/unknown command or unsafe syntax.
     */
    public static function tokenize(string $line, array $allowedCommands): array
    {
        $tokens = [];
        $token = '';
        $started = false;
        $quote = null;
        $length = strlen($line);

        for ($index = 0; $index < $length; $index++) {
            $character = $line[$index];
            if ($character === "\0" || $character === "\r" || $character === "\n") {
                throw new \InvalidArgumentException('A command line must not contain NUL or line breaks.');
            }

            if ($character === '\\') {
                $next = $line[$index + 1] ?? null;
                if ($next !== null && ($next === '"' || $next === "'" || $next === '\\'
                    || ($quote === null && $next === ' '))) {
                    $token .= $next;
                    $started = true;
                    $index++;
                } else {
                    // Other backslashes, including Windows separators, are literal.
                    $token .= '\\';
                    $started = true;
                }
                continue;
            }

            if ($quote !== null) {
                if ($character === $quote) {
                    $quote = null;
                } else {
                    $token .= $character;
                }
                $started = true;
                continue;
            }

            if ($character === '"' || $character === "'") {
                $quote = $character;
                $started = true;
                continue;
            }
            if ($character === ' ' || $character === "\t") {
                if ($started) {
                    $tokens[] = $token;
                    $token = '';
                    $started = false;
                }
                continue;
            }
            if (str_contains(';|&<>`$%()!', $character)) {
                throw new \InvalidArgumentException('Unquoted shell syntax is not accepted in a command line.');
            }
            $token .= $character;
            $started = true;
        }

        if ($quote !== null) {
            throw new \InvalidArgumentException('Unterminated quoted command argument.');
        }
        if ($started) {
            $tokens[] = $token;
        }
        if ($tokens === [] || !in_array($tokens[0], $allowedCommands, true)) {
            throw new \InvalidArgumentException('Command is empty or not registered by the caller.');
        }

        return $tokens;
    }
}
