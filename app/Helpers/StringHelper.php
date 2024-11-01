<?php

namespace App\Helpers;

class StringHelper
{
    public static function getSimilarity(string $str1, string $str2): float
    {
        // Remove numbers and special characters for comparison
        $cleanStr1 = preg_replace('/[0-9]+/', '', $str1);
        $cleanStr2 = preg_replace('/[0-9]+/', '', $str2);

        // Calculate similarity
        similar_text($cleanStr1, $cleanStr2, $percent);

        return $percent;
    }
}
