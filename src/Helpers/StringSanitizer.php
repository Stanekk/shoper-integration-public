<?php

namespace App\Helpers;

class StringSanitizer {
    public static function sanitize(string $value): string
    {
        $value = str_replace("\xA0", ' ', $value);
        return trim(preg_replace('/[^\PC\s]/u', '', $value));
    }
}
