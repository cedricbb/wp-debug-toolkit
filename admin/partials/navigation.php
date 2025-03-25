<?php
/**
 * Navigation entre les onglets
 * @package WP_Debug_Toolkit
 */

if (!defined('ABSPATH')) {
    exit;
}

// Récupérer tous les onglets via le filtre
$tabs = apply_filters('wp_debug_toolkit_tabs', [
    'dashboard' => [
        'title' => __('Tableau de bord', 'wp-debug-toolkit'),
        'icon' => 'dashicons-dashboard',
    ],
    'settings' => [
        'title' => __('Paramètres', 'wp-debug-toolkit'),
        'icon' => 'dashicons-admin-settings',
    ],
    'about' => [
        'title' => __('À propos', 'wp-debug-toolkit'),
        'icon' => 'dashicons-info',
    ]
]);

// Déterminer l'onglet actif
$currentTab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'dashboard';

// Vérifier si l'onglet existe
if (!isset($tabs[$currentTab])) {
    $currentTab = 'dashboard';
}
?>
<div class="wp-debug-toolkit-navigation">
    <nav class="nav-tab-wrapper wp-clearfix">
        <?php foreach ($tabs as $tabId => $tab) : ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wp-debug-toolkit&tab=' . $tabId)); ?>" class="nav-tab <?php echo $currentTab === $tabId ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons <?php echo esc_attr($tab['icon']); ?>"></span>
                <?php echo esc_html($tab['title']); ?>
            </a>
        <?php endforeach; ?>
    </nav>
</div>

