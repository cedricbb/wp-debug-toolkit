<?php
/**
 * Fonctions utilitaires pour le plugin WP DebugToolkit
 * @package WP_Debug_Toolkit
 */

use Elementor\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Journaliser un message dans les logs du plugin
 * @param string $message Message à journaliser
 * @param string $level Niveau de log (info, warning, error)
 * @param string $context Contexte du log (outil ou fonctionnalité)
 * @return bool True si le log a été enregistré, false sinon
 */
function wpDebugToolkitLog(string $message, string $level = 'info', string $context = 'general'): bool
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
    return (bool) file_put_contents($logFile, $formattedLog, FILE_APPEND | LOCK_EX);
}

/**
 * Récupérer les logs du plugin
 * @param string $date Date des logs format Y-m-d)
 * @param string $level Niveau de log à filtrer (optionnel)
 * @param string $context Contexte à filtrer (optionnel)
 * @return array Tableau des entées de log
 */
function wpDebugToolkitGetLogs(string $date, string $level = '', string $context = ''): array
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

/**
 * Formater des données pour l'affichage
 *
 * @param mixed $data Données à formater
 * @param bool $echo Afficher directement ou retourner
 * @return string|void Données formatées
 */
function wpDebugToolkitFormatData(mixed $data, bool $echo = true) {
    $output = '';

    if (is_array($data) || is_object($data)) {
        $output = '<pre>' . esc_html(print_r($data, true)) . '</pre>';
    } else {
        $output = '<pre>' . esc_html(var_export($data, true)) . '</pre>';
    }

    if ($echo) {
        echo $output;
    } else {
        return $output;
    }
}

/**
 * Vérifier si Elementor est actif
 *
 * @return bool True si Elementor est actif
 */
function wpDebugToolkitIsElementorActive(): bool
{
    return defined('ELEMENTOR_VERSION') && is_plugin_active('elementor/elementor.php');
}

/**
 * Récupérer les informations système
 *
 * @return array Informations système
 */
function wpDebugToolkitGetSystemInfo(): array
{
    global $wpdb;

    // Récupérer les informations WordPress
    $wp_info = array(
        'version' => get_bloginfo('version'),
        'home_url' => home_url(),
        'site_url' => site_url(),
        'is_multisite' => is_multisite(),
        'max_upload_size' => size_format(wp_max_upload_size()),
        'memory_limit' => WP_MEMORY_LIMIT,
        'timezone' => get_option('timezone_string')
    );

    // Récupérer les informations sur la base de données
    $db_info = array(
        'version' => $wpdb->db_version(),
        'prefix' => $wpdb->prefix,
        'charset' => $wpdb->charset
    );

    // Récupérer les informations serveur
    $server_info = array(
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'],
        'server_os' => PHP_OS,
        'max_execution_time' => ini_get('max_execution_time'),
        'post_max_size' => ini_get('post_max_size'),
        'max_input_vars' => ini_get('max_input_vars'),
        'display_errors' => ini_get('display_errors')
    );

    // Récupérer les informations sur les plugins actifs
    $active_plugins = array();
    $plugins = get_plugins();

    foreach ($plugins as $plugin_path => $plugin_info) {
        if (is_plugin_active($plugin_path)) {
            $active_plugins[$plugin_path] = array(
                'name' => $plugin_info['Name'],
                'version' => $plugin_info['Version'],
                'author' => $plugin_info['Author']
            );
        }
    }

    // Récupérer les informations sur le thème
    $theme = wp_get_theme();
    $theme_info = array(
        'name' => $theme->get('Name'),
        'version' => $theme->get('Version'),
        'author' => $theme->get('Author'),
        'parent_theme' => $theme->parent() ? $theme->parent()->get('Name') : null
    );

    // Combiner toutes les informations
    return array(
        'wordpress' => $wp_info,
        'database' => $db_info,
        'server' => $server_info,
        'active_plugins' => $active_plugins,
        'theme' => $theme_info
    );
}

/**
 * Récupérer la taille d'une table de base de données
 *
 * @param string $table_name Nom de la table sans préfixe
 * @return string Taille formatée
 */
function wpDebugToolkitGetTableSize(string $table_name): string
{
    global $wpdb;

    $table = $wpdb->prefix . $table_name;
    $result = $wpdb->get_row($wpdb->prepare("
        SELECT 
            table_name AS 'table',
            round(((data_length + index_length) / 1024 / 1024), 2) AS 'size'
        FROM information_schema.TABLES
        WHERE table_schema = %s
        AND table_name = %s
    ", DB_NAME, $table));

    if ($result) {
        return $result->size . ' MB';
    }

    return '0 MB';
}

/**
 * Récupérer la liste des blocs Elementor disponibles
 *
 * @return array Liste des widgets Elementor
 */
function wpDebugToolkitGetElementorWidgets(): array
{
    if (!wpDebugToolkitIsElementorActive()) {
        return array();
    }

    $widgets = array();

    // Récupérer le gestionnaire de widgets
    $widgets_manager = Plugin::instance()->widgets_manager;

    if ($widgets_manager) {
        $widget_types = $widgets_manager->get_widget_types();

        foreach ($widget_types as $widget) {
            $widgets[$widget->get_name()] = array(
                'name' => $widget->get_title(),
                'icon' => $widget->get_icon(),
                'categories' => $widget->get_categories()
            );
        }
    }

    return $widgets;
}

/**
 * Récupérer la liste des hooks WordPress actifs
 *
 * @return array Liste des hooks
 */
function wpDebugToolkitGetActiveHooks(): array
{
    global $wp_filter;

    $hooks = array();

    // Parcourir tous les hooks enregistrés
    foreach ($wp_filter as $hook_name => $hook_obj) {
        // Ignorer certains hooks trop courants
        if (in_array($hook_name, array('the_content', 'admin_notices', 'wp_head'))) {
            continue;
        }

        $hooks[$hook_name] = array();

        // Récupérer toutes les priorités
        foreach ($hook_obj as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $callback_info = array(
                    'priority' => $priority
                );

                // Déterminer le type de callback
                if (is_array($callback['function'])) {
                    if (is_object($callback['function'][0])) {
                        $callback_info['type'] = 'class';
                        $callback_info['class'] = get_class($callback['function'][0]);
                        $callback_info['method'] = $callback['function'][1];
                    } else {
                        $callback_info['type'] = 'static';
                        $callback_info['class'] = $callback['function'][0];
                        $callback_info['method'] = $callback['function'][1];
                    }
                } elseif (is_string($callback['function'])) {
                    $callback_info['type'] = 'function';
                    $callback_info['function'] = $callback['function'];
                } else {
                    $callback_info['type'] = 'closure';
                }

                $hooks[$hook_name][] = $callback_info;
            }
        }
    }

    return $hooks;
}
