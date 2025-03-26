<?php
/**
 * Vue du tableau de bord
 *
 * @package WP_Debug_Toolkit
 */

// Si ce fichier est appelé directement, on sort.
use WPDebugToolkit\Util\SystemInfo;

if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les informations système
$system_info = SystemInfo::getSystemInfo();

// Récupérer tous les outils via le filtre
$tools = apply_filters('wp_debug_toolkit_tools', array(
    'elementor-block-analyzer' => array(
        'title' => __('Analyseur de blocs Elementor', 'wp-debug-toolkit'),
        'description' => __('Analyse l\'utilisation des widgets Elementor sur le site', 'wp-debug-toolkit'),
        'icon' => 'dashicons-welcome-widgets-menus',
        'active' => true
    ),
    'elementor-form-analyzer' => array(
        'title' => __('Analyseur de formulaires Elementor', 'wp-debug-toolkit'),
        'description' => __('Analyse la configuration des formulaires Elementor', 'wp-debug-toolkit'),
        'icon' => 'dashicons-forms',
        'active' => true
    ),
    'api-monitor' => array(
        'title' => __('Moniteur d\'API', 'wp-debug-toolkit'),
        'description' => __('Surveille les appels API REST entrants et sortants', 'wp-debug-toolkit'),
        'icon' => 'dashicons-rest-api',
        'active' => true
    ),
    'media-cleaner' => array(
        'title' => __('Nettoyeur de médias', 'wp-debug-toolkit'),
        'description' => __('Identifie et nettoie les médias inutilisés', 'wp-debug-toolkit'),
        'icon' => 'dashicons-images-alt2',
        'active' => true
    ),
    'hook-inspector' => array(
        'title' => __('Inspecteur de hooks', 'wp-debug-toolkit'),
        'description' => __('Visualise les actions et filtres WordPress', 'wp-debug-toolkit'),
        'icon' => 'dashicons-admin-links',
        'active' => true
    ),
    'query-profiler' => array(
        'title' => __('Profileur de requêtes SQL', 'wp-debug-toolkit'),
        'description' => __('Analyse les performances des requêtes SQL', 'wp-debug-toolkit'),
        'icon' => 'dashicons-database',
        'active' => true
    ),
    'cache-inspector' => array(
        'title' => __('Inspecteur de cache', 'wp-debug-toolkit'),
        'description' => __('Examine les objets en cache', 'wp-debug-toolkit'),
        'icon' => 'dashicons-performance',
        'active' => true
    ),
    'cron-monitor' => array(
        'title' => __('Moniteur de cron', 'wp-debug-toolkit'),
        'description' => __('Surveille les tâches planifiées WordPress', 'wp-debug-toolkit'),
        'icon' => 'dashicons-clock',
        'active' => true
    )
));
?>

<div class="wp-debug-toolkit-dashboard">
    <div class="wp-debug-toolkit-welcome">
        <h2><?php echo esc_html__('Bienvenue dans WP Debug Toolkit', 'wp-debug-toolkit'); ?></h2>
        <p class="description">
            <?php echo esc_html__('Une suite complète d\'outils de débogage et d\'analyse pour WordPress. Utilisez les outils ci-dessous pour analyser et optimiser votre site.', 'wp-debug-toolkit'); ?>
        </p>
    </div>

    <div class="wp-debug-toolkit-system-info">
        <h3><?php echo esc_html__('Informations système', 'wp-debug-toolkit'); ?></h3>
        <div class="wp-debug-toolkit-card">
            <div class="wp-debug-toolkit-card-body">
                <div class="wp-debug-toolkit-system-info-grid">
                    <div class="wp-debug-toolkit-system-info-item">
                        <span class="label"><?php echo esc_html__('WordPress', 'wp-debug-toolkit'); ?></span>
                        <span class="value"><?php echo esc_html($system_info['wordpress']['version']); ?></span>
                    </div>
                    <div class="wp-debug-toolkit-system-info-item">
                        <span class="label"><?php echo esc_html__('PHP', 'wp-debug-toolkit'); ?></span>
                        <span class="value"><?php echo esc_html($system_info['server']['php_version']); ?></span>
                    </div>
                    <div class="wp-debug-toolkit-system-info-item">
                        <span class="label"><?php echo esc_html__('Base de données', 'wp-debug-toolkit'); ?></span>
                        <span class="value"><?php echo esc_html($system_info['database']['version']); ?></span>
                    </div>
                    <div class="wp-debug-toolkit-system-info-item">
                        <span class="label"><?php echo esc_html__('Serveur', 'wp-debug-toolkit'); ?></span>
                        <span class="value"><?php echo esc_html($system_info['server']['server_software']); ?></span>
                    </div>
                    <div class="wp-debug-toolkit-system-info-item">
                        <span class="label"><?php echo esc_html__('Thème', 'wp-debug-toolkit'); ?></span>
                        <span class="value"><?php echo esc_html($system_info['theme']['name'] . ' ' . $system_info['theme']['version']); ?></span>
                    </div>
                    <div class="wp-debug-toolkit-system-info-item">
                        <span class="label"><?php echo esc_html__('Plugins actifs', 'wp-debug-toolkit'); ?></span>
                        <span class="value"><?php echo esc_html(count($system_info['active_plugins'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="wp-debug-toolkit-tools-grid">
        <h3><?php echo esc_html__('Outils disponibles', 'wp-debug-toolkit'); ?></h3>
        <div class="wp-debug-toolkit-tools-list">
            <?php foreach ($tools as $tool_id => $tool) : ?>
                <div class="wp-debug-toolkit-tool-card">
                    <div class="wp-debug-toolkit-tool-card-header">
                        <span class="dashicons <?php echo esc_attr($tool['icon']); ?>"></span>
                        <h4><?php echo esc_html($tool['title']); ?></h4>
                    </div>
                    <div class="wp-debug-toolkit-tool-card-body">
                        <p><?php echo esc_html($tool['description']); ?></p>
                    </div>
                    <div class="wp-debug-toolkit-tool-card-footer">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=wp-debug-toolkit&tab=' . $tool_id)); ?>" class="button button-crayola">
                            <?php echo esc_html__('Ouvrir', 'wp-debug-toolkit'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>