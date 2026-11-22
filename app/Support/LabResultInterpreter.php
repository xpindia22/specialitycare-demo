<?php

namespace App\Support;

class LabResultInterpreter
{
    public static function interpret(mixed $resultValue, ?string $normalRange): array
    {
        $valueText = trim((string) $resultValue);
        $rangeText = trim((string) $normalRange);

        if ($valueText === '') {
            return self::result('N', 'Normal', 'normal');
        }

        $qualitative = self::interpretQualitative($valueText, $rangeText);
        if ($qualitative !== null) {
            return $qualitative;
        }

        $numericValue = self::firstNumber($valueText);
        if ($numericValue === null || $rangeText === '') {
            return self::result('ABN', 'Review', 'review');
        }

        $numeric = self::interpretNumeric($numericValue, $rangeText);
        if ($numeric !== null) {
            return $numeric;
        }

        return self::result('ABN', 'Review', 'review');
    }

    public static function labelForCode(?string $code): string
    {
        return match ($code) {
            'H' => 'High',
            'L' => 'Low',
            'HH' => 'Critical High',
            'LL' => 'Critical Low',
            'POS' => 'Positive',
            'NEG' => 'Negative',
            'ABN' => 'Abnormal',
            default => 'Normal',
        };
    }

    public static function severityForCode(?string $code): string
    {
        return match ($code) {
            'H', 'L', 'ABN' => 'abnormal',
            'HH', 'LL', 'POS' => 'critical',
            default => 'normal',
        };
    }

    private static function interpretQualitative(string $valueText, string $rangeText): ?array
    {
        $value = strtolower($valueText);
        $range = strtolower($rangeText);

        $positiveTerms = ['positive', 'reactive', 'detected', 'present', 'seen', 'growth'];
        $negativeTerms = ['negative', 'non-reactive', 'non reactive', 'not detected', 'absent', 'not seen', 'no growth'];

        $isNegative = self::containsAny($value, $negativeTerms);
        $isPositive = !$isNegative && self::containsAny($value, $positiveTerms);
        $normalIsNegative = self::containsAny($range, $negativeTerms);
        $normalIsPositive = self::containsAny($range, ['positive', 'present']);

        if ($isPositive) {
            if ($normalIsNegative) {
                return self::result('POS', 'Positive', 'critical');
            }

            return self::result($normalIsPositive ? 'N' : 'POS', $normalIsPositive ? 'Normal' : 'Positive', $normalIsPositive ? 'normal' : 'critical');
        }

        if ($isNegative) {
            if ($normalIsPositive) {
                return self::result('NEG', 'Negative', 'critical');
            }

            return self::result('NEG', 'Negative', 'normal');
        }

        if (self::containsAny($range, ['negative', 'absent', 'not seen', 'no growth']) && $value !== '') {
            return self::result('ABN', 'Abnormal', 'abnormal');
        }

        return null;
    }

    private static function interpretNumeric(float $value, string $rangeText): ?array
    {
        $range = str_replace(['>=', '<=', '–', '—'], ['>=', '<=', '-', '-'], $rangeText);
        $range = preg_replace('/\s+/', ' ', $range);

        preg_match_all('/(<=|>=|<|>)\s*(-?\d+(?:\.\d+)?)/', $range, $comparators, PREG_SET_ORDER);
        foreach ($comparators as $match) {
            $operator = $match[1];
            $limit = (float) $match[2];

            if ($operator === '<' && $value >= $limit) {
                return self::result('H', 'High', 'abnormal');
            }
            if ($operator === '<=' && $value > $limit) {
                return self::result('H', 'High', 'abnormal');
            }
            if ($operator === '>' && $value <= $limit) {
                return self::result('L', 'Low', 'abnormal');
            }
            if ($operator === '>=' && $value < $limit) {
                return self::result('L', 'Low', 'abnormal');
            }
        }

        if (!empty($comparators)) {
            return self::result('N', 'Normal', 'normal');
        }

        preg_match_all('/(-?\d+(?:\.\d+)?)\s*-\s*(-?\d+(?:\.\d+)?)/', $range, $ranges, PREG_SET_ORDER);
        if (empty($ranges)) {
            return null;
        }

        // If the range contains sex/age specific alternatives, pick the first
        // numeric interval as a conservative fallback. The stored range text is
        // still shown beside the result for clinical review.
        $low = (float) $ranges[0][1];
        $high = (float) $ranges[0][2];

        if ($value < $low) {
            return self::result('L', 'Low', 'abnormal');
        }
        if ($value > $high) {
            return self::result('H', 'High', 'abnormal');
        }

        return self::result('N', 'Normal', 'normal');
    }

    private static function firstNumber(string $value): ?float
    {
        if (!preg_match('/-?\d+(?:\.\d+)?/', $value, $match)) {
            return null;
        }

        return (float) $match[0];
    }

    private static function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private static function result(string $code, string $label, string $severity): array
    {
        return ['code' => $code, 'label' => $label, 'severity' => $severity];
    }
}
