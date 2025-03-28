<?php

namespace WPDebugToolkit\Util;

class SystemInfo
{
    public static function getSystemInfo(): array
    {
        global $wpdb;

        // Récupérer les informations WordPress
        $wpInfo = [
            'version' => get_bloginfo('version'),
            'home_url' => home_url(),
            'site_url' => site_url(),
            'is_multisite' => is_multisite(),
            'max_upload_size' => size_format(wp_max_upload_size()),
            'memory_limit' => WP_MEMORY_LIMIT,
            'timezone' => get_option('timezone_string'),
        ];

        // Récupérer les informations sur la base de données
        $dbInfo = [
            'version' => $wpdb->db_version(),
            'prefix' => $wpdb->prefix,
            'tables' => $wpdb->tables(),
        ];

        // Récupérer les informations sur le serveur
        $serveurInfo = [
            'php_version' => phpversion(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'],
            'server_os' => PHP_OS,
            'max_execution_time' => ini_get('max_execution_time'),
            'post_max_size' => ini_get('post_max_size'),
            'max_input_vars' => ini_get('max_input_vars'),
            'display_errors' => ini_get('display_errors')
        ];

        // Récupérer les informations sur les plugins actifs
        $activePlugins = [];
        $plugins = get_plugins();

        foreach ($plugins as $pluginPath => $pluginInfo) {
            if (is_plugin_active($pluginPath)) {
                $activePlugins[$pluginPath] = [
                    'name' => $pluginInfo['Name'],
                    'version' => $pluginInfo['Version'],
                    'author' => $pluginInfo['Author'],
                ];
            }
        }

        // Récupérer les informations sur le thème actif
        $theme = wp_get_theme();
        $themeInfo = [
            'name' => $theme->get('Name'),
            'version' => $theme->get('Version'),
            'author' => $theme->get('Author'),
            'parent_theme' => $theme->parent() ? $theme->parent()->get('Name') : null,
        ];

        // Combiner toutes les informations
        return [
            'wordpress' => $wpInfo,
            'database' => $dbInfo,
            'server' => $serveurInfo,
            'active_plugins' => $activePlugins,
            'theme' => $themeInfo,
        ];
    }
}
