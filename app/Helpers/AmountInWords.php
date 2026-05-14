<?php

namespace App\Helpers;

class AmountInWords
{
    /**
     * Convert a numeric amount to words (integer part only).
     * Example: 1234.56 -> "One Thousand Two Hundred Thirty Four Only"
     */
    public static function convert($amount): string
    {
        $ones = [
            '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
            'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
            'Seventeen', 'Eighteen', 'Nineteen'
        ];

        $tens = [
            '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'
        ];

        $thousands = ['', 'Thousand', 'Million', 'Billion'];

        $amount = (int) floor((float) $amount);

        if ($amount === 0) {
            return 'Zero Only';
        }

        $result = '';

        for ($i = 0; $amount > 0; $i++) {
            $group = $amount % 1000;
            if ($group !== 0) {
                $groupWords = self::convertGroup($group, $ones, $tens);
                $result = trim($groupWords . ' ' . $thousands[$i] . ' ' . $result);
            }
            $amount = (int) floor($amount / 1000);
        }

        return trim($result) . ' Only';
    }

    private static function convertGroup(int $num, array $ones, array $tens): string
    {
        $result = '';

        if ($num >= 100) {
            $result .= $ones[(int) floor($num / 100)] . ' Hundred ';
            $num %= 100;
        }

        if ($num >= 20) {
            $result .= $tens[(int) floor($num / 10)] . ' ';
            $num %= 10;
        }

        if ($num > 0) {
            $result .= $ones[$num] . ' ';
        }

        return trim($result);
    }
}

