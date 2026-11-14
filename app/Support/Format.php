<?php

namespace App\Support;

use Carbon\CarbonInterface;

/** Format tampilan (gaya id-ID), padanan lib/format.js versi React. */
class Format
{
    public static function num(?float $value, int $digits = 1): string
    {
        return $value === null ? '—' : number_format($value, $digits, ',', '.');
    }

    public static function relTime(?CarbonInterface $t): string
    {
        if (! $t) {
            return 'belum ada data';
        }
        $minutes = (int) floor($t->diffInMinutes(now(), true));
        if ($minutes < 1) {
            return 'baru saja';
        }
        if ($minutes < 60) {
            return "$minutes menit lalu";
        }
        $hours = intdiv($minutes, 60);

        return $hours < 24 ? "$hours jam lalu" : intdiv($hours, 24).' hari lalu';
    }
}
