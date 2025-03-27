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