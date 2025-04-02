<?php
/**
 * Vue des détails d'un widget Elementor
 *
 * @package WP_Debug_Toolkit
 */

// Protection contre l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Vérifier que les données du widget sont disponibles
if (empty($widgetData)) {
    echo '<p class="error">' . __('Aucune donnée disponible pour ce widget.', 'wp-debug-toolkit') . '</p>';
    return;
}

$instanceTitle = $widgetData['instance'] ? $widgetData['instance']->get_title() : $widgetData['name'];
$totalUses = $widgetData['total_uses'] ?? 0;
$firstUsage = $widgetData['first_usage'] ?? __('Données non disponibles', 'wp-debug-toolkit');
$averageUses = $widgetData['average_uses'] ?? '0';
?>

<div class="elementor-block-analyzer-widget-details">
    <div class="widget-details-header">
        <div class="widget-icon">
            <i class="<?php echo esc_attr($widgetData['icon']); ?>"></i>
        </div>
        <div class="widget-info">
            <h2><?php echo esc_html($instanceTitle); ?></h2>
        </div>
    </div>

    <div class="widget-details-body">
        <div class="widget-stats-grid">
            <div class="widget-stat-item">
                <span class="stat-value"><?php echo esc_html($totalUses); ?></span>
                <span class="stat-label"><?php _e('Utilisations totales', 'wp-debug-toolkit'); ?></span>
            </div>
            <div class="widget-stat-item">
                <span class="stat-value"><?php echo count($widgetData['posts'] ?? []); ?></span>
                <span class="stat-label"><?php _e('Pages/Articles', 'wp-debug-toolkit'); ?></span>
            </div>
            <div class="widget-stat-item">
                <span class="stat-value"><?php echo count($widgetData['templates'] ?? []); ?></span>
                <span class="stat-label"><?php _e('Templates', 'wp-debug-toolkit'); ?></span>
            </div>
            <div class="widget-stat-item">
                <span class="stat-value"><?php echo count($widgetData['theme_elements'] ?? []); ?></span>
                <span class="stat-label"><?php _e('Éléments de thème', 'wp-debug-toolkit'); ?></span>
            </div>
            <div class="widget-stat-item">
                <span class="stat-value"><?php echo esc_html($firstUsage); ?></span>
                <span class="stat-label"><?php _e('Première utilisation', 'wp-debug-toolkit'); ?></span>
            </div>
            <div class="widget-stat-item">
                <span class="stat-value"><?php echo esc_html($averageUses); ?></span>
                <span class="stat-label"><?php _e('Moyenne par page', 'wp-debug-toolkit'); ?></span>
            </div>
        </div>

        <div id="usage_history" class="postbox closed">
            <div class="postbox-header">
                <h2 class="hndle">
                    <span class="dashicons dashicons-chart-line"></span>
                    <?php esc_html_e('Historique d\'utilisation', 'cc-debug-tool'); ?>
                </h2>
                <div class="handle-actions hide-if-no-js">
                    <button type="button" class="handlediv" aria-expanded="true">
                        <span class="screen-reader-text"><?php esc_html_e('Basculer le panneau', 'cc-debug-tool'); ?></span>
                        <span class="toggle-indicator" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
            <div class="inside">
                <div class="chart-container">
                    <canvas id="usageChart"></canvas>
                </div>
            </div>
        </div>

        <div class="widget-usage-details">
            <div class="widget-usage-tabs">
                <div class="widget-tabs-content">
                    <?php if (!empty($widgetData['posts'])): ?>
                        <div class="tab-posts">
                            <h3>
                                <span class="dashicons dashicons-admin-page"></span>
                                <?php esc_html_e('Pages et Articles', 'wp-debug-toolkit'); ?> (<?php echo count($widgetData['posts']); ?>)
                            </h3>
                            <ul class="usage-list">
                                <?php foreach ($widgetData['posts'] as $postId => $postTitle): ?>
                                    <li>
                                        <span class="dashicons dashicons-admin-page"></span>
                                        <a href="<?php
                                        $edit_url = admin_url(sprintf('post.php?post=%d&action=elementor', $postId));
                                        echo $edit_url; ?>" target="_blank">
                                            <?php echo esc_html($postTitle); ?>
                                            <span class="edit-link"><span class="dashicons dashicons-edit"></span></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($widgetData['templates'])): ?>
                        <div class="tab-templates">
                            <h3>
                                <span class="dashicons dashicons-editor-table"></span>
                                <?php esc_html_e('Templates', 'cc-debug-tool'); ?>
                            </h3>
                            <ul class="usage-list">
                                <?php foreach ($widgetData['templates'] as $templateId => $templateTitle): ?>
                                    <li>
                                        <span class="dashicons dashicons-editor-table"></span>
                                        <a href="<?php
                                        $edit_url = admin_url(sprintf('post.php?post=%d&action=elementor', $templateId));
                                        echo $edit_url; ?>" target="_blank">
                                            <?php echo esc_html($templateTitle); ?>
                                            <span class="edit-link"><span class="dashicons dashicons-edit"></span></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($widgetData['popups'])): ?>
                        <div class="tab-popups">
                            <h3>
                                <span class="dashicons dashicons-layout"></span>
                                <?php esc_html_e('Éléments de thème', 'cc-debug-tool'); ?>
                            </h3>
                            <ul class="usage-list">
                                <?php foreach ($widgetData['popups'] as $popupId => $popupTitle): ?>
                                    <li>
                                        <span class="dashicons dashicons-layout"></span>
                                        <a href="<?php
                                        $edit_url = admin_url(sprintf('post.php?post=%d&action=elementor', $popupId));
                                        echo $edit_url; ?>" target="_blank">
                                            <?php echo esc_html($popupTitle); ?>
                                            <span class="edit-link"><span class="dashicons dashicons-edit"></span></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($widgetData['theme_elements'])): ?>
                        <div class="tab-theme_elements">
                            <h3>
                                <span class="dashicons dashicons-layout"></span>
                                <?php esc_html_e('Éléments de thème', 'cc-debug-tool'); ?>
                            </h3>
                            <ul class="usage-list">
                                <?php foreach ($widgetData['theme_elements'] as $elementId => $element): ?>
                                    <?php
                                    $elementType = $element['type'] ?? 'unknown';
                                    $elementTitle = $element['title'] ?? __('Sans titre', 'wp-debug-toolkit');
                                    $iconClass = '';

                                    switch ($elementType) {
                                        case 'header':
                                            $iconClass = 'dashicons-welcome-widgets-menus';
                                            $typeLabel = __('En-tête', 'wp-debug-toolkit');
                                            break;
                                        case 'footer':
                                            $iconClass = 'dashicons-arrow-down-alt';
                                            $typeLabel = __('Pied de page', 'wp-debug-toolkit');
                                            break;
                                        case '404':
                                            $iconClass = 'dashicons-warning';
                                            $typeLabel = __('Page 404', 'wp-debug-toolkit');
                                            break;
                                        case 'single':
                                            $iconClass = 'dashicons-admin-post';
                                            $typeLabel = __('Article', 'wp-debug-toolkit');
                                            break;
                                        case 'archive':
                                            $iconClass = 'dashicons-archive';
                                            $typeLabel = __('Archive', 'wp-debug-toolkit');
                                            break;
                                        default:
                                            $iconClass = 'dashicons-admin-generic';
                                            $typeLabel = $elementType;
                                    }
                                    ?>
                                    <li>
                                        <span class="dashicons <?php echo esc_attr($iconClass); ?>"></span>
                                        <a href="<?php
                                        $edit_url = admin_url(sprintf('post.php?post=%d&action=elementor', $elementId));
                                        echo $edit_url; ?>" target="_blank">
                                            <span class="element-title"><?php echo esc_html($elementTitle); ?></span>
                                            <span class="element-type"> (<?php echo esc_html($typeLabel); ?>)</span>
                                            <span class="edit-link"></span><span class="dashicons dashicons-edit"></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
