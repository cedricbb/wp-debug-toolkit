<?php

namespace WPDebugToolkit\Util;

class StatsHelper
{
    /**
     * Calcule le nombre total d'utilisations
     */
    public static function calculateTotalUses(array $data): int
    {
        return count($data['posts'] ?? []) +
            count($data['templates'] ?? []) +
            count($data['popups'] ?? []) +
            count($data['theme_elements'] ?? []);
    }

    /**
     * Calcule la moyenne d'utilisation par page
     */
    public static function calculateAverageUsesPerPage(array $data): string
    {
        $totalUses = self::calculateTotalUses($data);

        $totalPages = count($data['posts'] ?? []) +
            count($data['templates'] ?? []) +
            count($data['popups'] ?? []) +
            count($data['theme_elements'] ?? []);
        if ($totalPages === 0) {
            return '0';
        }
        // Arrondir à une décimale
        $average = round($totalUses / $totalPages, 1);

        return (string)$average;
    }
}