<?php

namespace WPDebugToolkit\Admin\Page;

use WPDebugToolkit\Util\SystemInfo;
class Dashboard extends AbstractPage
{
    public function __construct()
    {
        parent::__construct('dashboard', __('Tableau de bord', 'wp-debug-toolkit'), 'dashicons-dashboard');
    }

    public function render(): void
    {
        // Afficher l'en-tête
        require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'src/Admin/View/header.php';

        // Récupérer les informations système
        $systemInfo = SystemInfo::getSystemInfo();

        // Récupérer tous les outils via le filtre
        $tools = apply_filters('wp_debug_toolkit_tools', [
            'elementor-block-analyzer' => [
                'title' => __('Analyseur de blocs Elementor', 'wp-debug-toolkit'),
                'description' => __('Analyse l\'utilisation des widgets Elementor sur le site', 'wp-debug-toolkit'),
                'icon' => 'dashicons-welcome-widgets-menus',
                'active' => true
            ],
            'elementor-form-analyzer' => [
                'title' => __('Analyseur de formulaires Elementor', 'wp-debug-toolkit'),
                'description' => __('Analyse la configuration des formulaires Elementor', 'wp-debug-toolkit'),
                'icon' => 'dashicons-forms',
                'active' => true
            ],
            'api-monitor' => [
                'title' => __('Moniteur d\'API', 'wp-debug-toolkit'),
                'description' => __('Surveille les appels API REST entrants et sortants', 'wp-debug-toolkit'),
                'icon' => 'dashicons-rest-api',
                'active' => true
            ],
            'media-cleaner' => [
                'title' => __('Nettoyeur de médias', 'wp-debug-toolkit'),
                'description' => __('Identifie et nettoie les médias inutilisés', 'wp-debug-toolkit'),
                'icon' => 'dashicons-images-alt2',
                'active' => true
            ],
            'hook-inspector' => [
                'title' => __('Inspecteur de hooks', 'wp-debug-toolkit'),
                'description' => __('Visualise les actions et filtres WordPress', 'wp-debug-toolkit'),
                'icon' => 'dashicons-admin-links',
                'active' => true
            ],
            'query-profiler' => [
                'title' => __('Profileur de requêtes SQL', 'wp-debug-toolkit'),
                'description' => __('Analyse les performances des requêtes SQL', 'wp-debug-toolkit'),
                'icon' => 'dashicons-database',
                'active' => true
            ],
            'cache-inspector' => [
                'title' => __('Inspecteur de cache', 'wp-debug-toolkit'),
                'description' => __('Examine les objets en cache', 'wp-debug-toolkit'),
                'icon' => 'dashicons-performance',
                'active' => true
            ],
            'cron-monitor' => [
                'title' => __('Moniteur de cron', 'wp-debug-toolkit'),
                'description' => __('Surveille les tâches planifiées WordPress', 'wp-debug-toolkit'),
                'icon' => 'dashicons-clock',
                'active' => true
            ],
        ]);

        // Inclure la vue du tableau de bord
        require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'src/Admin/View/dashboard.php';

        // Afficher le pied de page
        require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'src/Admin/View/footer.php';
    }
}
