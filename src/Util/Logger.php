<?php

namespace WPDebugUtil\Util;

class Logger
{

    public static function log(string $message, string $level = 'info', string $context = 'general'): bool
    {
        // Vérifier si la journalisation est activée
        $settings = get_option('wp_debug_toolkit_settings');
        if (!isset($settings['enable_logging']) || !$settings['enable_logging']) {
            return false;
        }

        // Préparer le message
        $logEntry = [
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'context' => $context,
            'message' => $message,
        ];

        // Déterminer le chemin du fichier de log
        $uploadDir = wp_upload_dir();
        $logsDir = $uploadDir['basedir'] . '/wp-debug-toolkit-logs';
        $logFile = $logsDir . '/' . date('Y-m-d') . '.log';

        // Créer le répertoire s'il n'existe pas
        if (!file_exists($logsDir)) {
            wp_mkdir_p($logsDir);
        }

        // Formater le message
        $formattedLog = sprintf(
            "[%s] [%s] [%s] %s\n",
            $logEntry['timestamp'],
            strtoupper($logEntry['level']),
            $logEntry['context'],
            $logEntry['message']
        );

        // Écrire dans le fichier
        return (bool)file_put_contents($logFile, $formattedLog, FILE_APPEND | LOCK_EX);
    }

    function getLogs(string $date = null, string $level = '', string $context = ''): array
    {
        // Utiliser la date actuelle si aucune date n'est spécifiée
        if (null === $date) {
            $date = date('Y-m-d');
        }

        // Déterminer le chemin du fichier de log
        $uploadDir = wp_upload_dir();
        $logsDir = $uploadDir['basedir'] . '/wp-debug-toolkit-logs';
        $logFile = $logsDir . '/' . $date . '.log';

        // Vérifier si le fichier de log existe
        if (!file_exists($logFile)) {
            return [];
        }

        // Lire le contenu du fichier
        $logContent = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // Traiter les entrées
        $logEntries = [];
        foreach ($logContent as $log) {
            // Extraire les informations de l'entrée de log
            if (preg_match('/\[(.*?)\] \[(.*?)\] \[(.*?)\] (.*)/', $log, $matches)) {
                $entry = [
                    'timestamp' => $matches[1],
                    'level' => strtolower($matches[2]),
                    'context' => $matches[3],
                    'message' => $matches[4],
                ];
                // Filtrer par niveau si nécessaire
                if (null !== $level && $entry['level'] !== strtolower($level)) {
                    continue;
                }

                // Filtrer par contexte si nécessaire
                if (null !== $context && $entry['context'] !== $context) {
                    continue;
                }
                $logEntries[] = $entry;
            }
        }
        return $logEntries;
    }
}
