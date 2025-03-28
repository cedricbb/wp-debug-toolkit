<?php

namespace WPDebugToolkit\Util;

final class DateHelper
{
    /**
     * Vérifie si une date est dans les 12 derniers mois
     */
    public static function isWithinLast12Months(string $year, string $month, string $currentYear, string $currentMonth): bool
    {
        $date = new \DateTime("$year-$month-01");
        $now = new \DateTime("$currentYear-$currentMonth-01");
        $interval = $date->diff($now);

        $monthsDiff = ($interval->y * 12) + $interval->m;
        return $monthsDiff <= 11 && $monthsDiff >= 0;
    }

    /**
     * Initialise un tableau des 12 derniers mois avec des valeurs à zéro
     */
    public static function initializeLast12Months(): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $timestamp = strtotime("-$i months");
            $yearMonth = date('Y-m', $timestamp);
            $months[$yearMonth] = 0;
        }
        return $months;
    }
}