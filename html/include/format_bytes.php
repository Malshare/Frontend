<?php

/**
 * Format a byte count into a human-readable decimal (base-1000) string,
 * e.g. 43_700_000_000_000 => "43.7 TB". Returns "0 B" for zero/negative.
 */
function format_bytes(int $bytes): string
{
    if ($bytes <= 0) {
        return '0 B';
    }
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $i = (int) floor(log($bytes, 1000));
    if ($i >= count($units)) {
        $i = count($units) - 1;
    }
    $value = $bytes / pow(1000, $i);
    $decimals = ($i === 0) ? 0 : 1;
    return number_format($value, $decimals) . ' ' . $units[$i];
}
