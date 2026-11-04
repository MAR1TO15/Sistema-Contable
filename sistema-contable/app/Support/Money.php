<?php

namespace App\Support;

/**
 * Money amounts handled as integer cents, so sums and comparisons are
 * exact (0.10 + 0.20 really is 0.30).
 */
class Money
{
    /**
     * Pattern of a valid non-negative amount with at most two decimals.
     */
    public const PATTERN = '/^\d{1,13}(\.\d{1,2})?$/';

    /**
     * Convert a validated amount such as "1500", "0.5" or "12.34" to cents.
     * Blank values count as zero.
     */
    public static function toCents(string|int|float|null $amount): int
    {
        $amount = trim((string) $amount);

        if ($amount === '') {
            return 0;
        }

        [$units, $decimals] = array_pad(explode('.', $amount, 2), 2, '');

        return (int) $units * 100 + (int) str_pad($decimals, 2, '0');
    }

    /**
     * Cents as a decimal string for the database, e.g. 123450 => "1234.50".
     */
    public static function fromCents(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    /**
     * Cents formatted for display, e.g. 123450 => "1,234.50".
     */
    public static function format(int $cents): string
    {
        return number_format(intdiv($cents, 100)).'.'.sprintf('%02d', $cents % 100);
    }
}
