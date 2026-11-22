<?php

if (! function_exists('mcms_num')) {
    /**
     * Format a numeric value removing redundant decimal trailing zeros
     * without accidentally stripping trailing zeros from integers (e.g. 10, 100).
     */
    function mcms_num(mixed $v, int $decimals = 2): string
    {
        if ($v === null || $v === '') {
            return '';
        }
        $f = (float) $v;
        $s = number_format($f, $decimals, '.', '');

        return str_contains($s, '.') ? (rtrim(rtrim($s, '0'), '.') ?: '0') : $s;
    }
}
