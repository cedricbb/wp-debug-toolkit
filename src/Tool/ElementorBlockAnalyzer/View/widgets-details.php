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

<div class="widget-details">
    <div class="widget-details-header">
        <div class="widget-icon">
            <i class="<?php echo esc_attr($widgetData['icon']); ?>"></i>
        </div>
        <div class="widget-info">
            <h2><?php echo esc_html($instanceTitle); ?></h2>
            <p class="widget-name"><?php echo esc_html($widgetData['name']); ?></p>
        </div>
    </div>

    <div class="widget-details-body">
        <div class="widget-meta">
            <div class="widget-stat">
                <span class="stat-label"><?php _e('Utilisations totales', 'wp-debug-toolkit'); ?></span>
                <span class="stat-value"><?php echo esc_html($totalUses); ?></span>
            </div>
            <div class="widget-stat">
                <span class="stat-label"><?php _e('Première utilisation', 'wp-debug-toolkit'); ?></span>
                <span class="stat-value"><?php echo esc_html($firstUsage); ?></span>
            </div>
            <div class="widget-stat">
                <span class="stat-label"><?php _e('Moyenne par page', 'wp-debug-toolkit'); ?></span>
                <span class="stat-value"><?php echo esc_html($averageUses); ?></span>
            </div>
        </div>

        <div class="widget-usage-chart-container">
            <h3><?php _e('Tendance d\'utilisation (12 derniers mois)', 'wp-debug-toolkit'); ?></h3>
            <canvas id="widget-usage-chart" width="700" height="300"></canvas>
        </div>

        <div class="widget-usage-details">
            <div class="widget-usage-tabs">
                <ul class="widget-tabs-nav">
                    <?php if (!empty($widgetData['posts'])): ?>
                        <li class="active" data-tab="posts"><?php _e('Pages/Posts', 'wp-debug-toolkit'); ?> (<?php echo count($widgetData['posts']); ?>)</li>
                    <?php endif; ?>

                    <?php if (!empty($widgetData['templates'])): ?>
                        <li <?php echo empty($widgetData['posts']) ? 'class="active"' : ''; ?> data-tab="templates"><?php _e('Templates', 'wp-debug-toolkit'); ?> (<?php echo count($widgetData['templates']); ?>)</li>
                    <?php endif; ?>

                    <?php if (!empty($widgetData['popups'])): ?>
                        <li <?php echo empty($widgetData['posts']) && empty($widgetData['templates']) ? 'class="active"' : ''; ?> data-tab="popups"><?php _e('Popups', 'wp-debug-toolkit'); ?> (<?php echo count($widgetData['popups']); ?>)</li>
                    <?php endif; ?>

                    <?php if (!empty($widgetData['theme_elements'])): ?>
                        <li <?php echo empty($widgetData['posts']) && empty($widgetData['templates']) && empty($widgetData['popups']) ? 'class="active"' : ''; ?> data-tab="theme_elements"><?php _e('Éléments de thème', 'wp-debug-toolkit'); ?> (<?php echo count($widgetData['theme_elements']); ?>)</li>
                    <?php endif; ?>
                </ul>

                <div class="widget-tabs-content">
                    <?php if (!empty($widgetData['posts'])): ?>
                        <div class="tab-content active" id="tab-posts">
                            <ul class="usage-list">
                                <?php foreach ($widgetData['posts'] as $postId => $postTitle): ?>
                                    <li>
                                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $postId . '&action=elementor')); ?>" target="_blank">
                                            <span class="dashicons dashicons-admin-page"></span>
                                            <?php echo esc_html($postTitle); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($widgetData['templates'])): ?>
                        <div class="tab-content <?php echo empty($widgetData['posts']) ? 'active' : ''; ?>" id="tab-templates">
                            <ul class="usage-list">
                                <?php foreach ($widgetData['templates'] as $templateId => $templateTitle): ?>
                                    <li>
                                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $templateId . '&action=elementor')); ?>" target="_blank">
                                            <span class="dashicons dashicons-admin-generic"></span>
                                            <?php echo esc_html($templateTitle); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($widgetData['popups'])): ?>
                        <div class="tab-content <?php echo empty($widgetData['posts']) && empty($widgetData['templates']) ? 'active' : ''; ?>" id="tab-popups">
                            <ul class="usage-list">
                                <?php foreach ($widgetData['popups'] as $popupId => $popupTitle): ?>
                                    <li>
                                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $popupId . '&action=elementor')); ?>" target="_blank">
                                            <span class="dashicons dashicons-layout"></span>
                                            <?php echo esc_html($popupTitle); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($widgetData['theme_elements'])): ?>
                        <div class="tab-content <?php echo empty($widgetData['posts']) && empty($widgetData['templates']) && empty($widgetData['popups']) ? 'active' : ''; ?>" id="tab-theme_elements">
                            <ul class="usage-list">
                                <?php foreach ($widgetData['theme_elements'] as $elementId => $element): ?>
                                    <?php
                                    $elementType = isset($element['type']) ? $element['type'] : 'unknown';
                                    $elementTitle = isset($element['title']) ? $element['title'] : __('Sans titre', 'wp-debug-toolkit');
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
                                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $elementId . '&action=elementor')); ?>" target="_blank">
                                            <span class="dashicons <?php echo esc_attr($iconClass); ?>"></span>
                                            <?php echo esc_html($elementTitle); ?>
                                            <span class="element-type">(<?php echo esc_html($typeLabel); ?>)</span>
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

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Gestion des onglets
        $('.widget-tabs-nav li').on('click', function() {
            var tabId = $(this).data('tab');

            // Activer l'onglet
            $('.widget-tabs-nav li').removeClass('active');
            $(this).addClass('active');

            // Afficher le contenu correspondant
            $('.tab-content').removeClass('active');
            $('#tab-' + tabId).addClass('active');
        });
    });
</script>