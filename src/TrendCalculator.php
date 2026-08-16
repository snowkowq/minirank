<?php

declare(strict_types=1);

namespace App;

final class TrendCalculator
{
    public const IMPROVED = 'improved';
    public const DECLINED = 'declined';
    public const STABLE   = 'stable';
    
    public const THRESHOLD = 3;

    // Lower position numbers are better (1 = top).
    // A positive diff means the keyword moved up in the ranking.
    public static function fromPositions(int $current, ?int $past): string
    {
        if ($past === null) {
            return self::STABLE;
        }

        $diff = $past - $current;

        if ($diff >= self::THRESHOLD) {
            return self::IMPROVED;
        }

        if ($diff <= -self::THRESHOLD) {
            return self::DECLINED;
        }

        return self::STABLE;
    }
}