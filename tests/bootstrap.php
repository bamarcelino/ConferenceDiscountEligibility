<?php

declare(strict_types=1);

if (! function_exists('__')) {
    function __(string $key, array $replace = []): string
    {
        foreach ($replace as $name => $value) {
            $key = str_replace(':' . $name, (string) $value, $key);
        }
        return $key;
    }
}


if (! function_exists('mb_strtolower')) {
    function mb_strtolower(string $value, ?string $encoding = null): string
    {
        return strtolower($value);
    }
}

if (! function_exists('mb_strlen')) {
    function mb_strlen(string $value, ?string $encoding = null): int
    {
        return strlen($value);
    }
}

require_once dirname(__DIR__) . '/vendor/autoload.php';
