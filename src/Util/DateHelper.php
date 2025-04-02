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

    /**
     * Obtient la date de première utilisation
     */
    public static function getFirstUseDate(array $data): ?int
    {
        $dates = [];

        foreach (['posts', 'templates', 'popups'] as $type) {
            if (!empty($data[$type])) {
                foreach (array_keys($data[$type]) as $postId) {
                    $postTime = get_post_time('U', false, $postId);
                    if ($postTime) {
                        $dates[] = $postTime;
                    }
                }
            }
        }

        if (!empty($data['theme_elements'])) {
            foreach (array_keys($data['theme_elements']) as $elementId) {
                $elementTime = get_post_time('U', false, $elementId);
                if ($elementTime) {
                    $dates[] = $elementTime;
                }
            }
        }

        return !empty($dates) ? min($dates) : null;
    }

    /**
     * Obtient l'historique d'utilisation
     */
    public static function getUsageHistory(array $data): array
    {
        $usageByMonth = self::initializeLast12Months();
        $currentYear = date('Y');
        $currentMonth = date('m');

        foreach (['posts', 'templates', 'popups'] as $type) {
            if (!empty($data[$type])) {
                foreach (array_keys($data[$type]) as $postId) {
                    $postDate = get_the_date('Y-m', $postId);
                    if ($postDate) {
                        $year = substr($postDate, 0, 4);
                        $month = substr($postDate, 5, 2);

                        if (self::isWithinLast12Months($year, $month, $currentYear, $currentMonth)
                            && isset($usageByMonth[$postDate])) {
                            $usageByMonth[$postDate]++;
                        }
                    }
                }
            }
        }

        if (!empty($data['theme_elements'])) {
            foreach (array_keys($data['theme_elements']) as $elementId) {
                $elementDate = get_the_date('Y-m', $elementId);
                if ($elementDate) {
                    $year = substr($elementDate, 0, 4);
                    $month = substr($elementDate, 5, 2);

                    if (self::isWithinLast12Months($year, $month, $currentYear, $currentMonth)
                        && isset($usageByMonth[$elementDate])) {
                        $usageByMonth[$elementDate]++;
                    }
                }
            }
        }

        return self::formatUsageHistory($usageByMonth);
    }

    /**
     * Formate l'historique d'utilisation
     */
    public static function formatUsageHistory(array $usageByMonth): array
    {
        $formattedHistory = [];
        foreach ($usageByMonth as $yearMonth => $count) {
            $timestamp = strtotime($yearMonth . '-01');
            $formattedMonth = date_i18n('M Y', $timestamp);
            $formattedHistory[$formattedMonth] = $count;
        }
        return $formattedHistory;
    }
}