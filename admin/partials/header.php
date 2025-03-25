<?php
/**
 * En-tête des pages d'administration
 * @package WP_Debug_Toolkit
 */

if (!defined('ABSPATH')) {
    exit;
}

// Déterminer l'onglet actif
$currentTab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'dashboard';
?>
<div class="wrap wp-debug-toolkit-wrapper">
    <div class="wp-debug-toolkit-header">
        <h1>
            <img src="<?php echo WP_DEBUG_TOOLKIT_PLUGIN_URL . 'assets/images/logo.svg'; ?>" alt="WP Debug Toolkit Logo" class="wp-debug-toolkit-logo" />
            <?php echo esc_html__('WP Debug Toolkit', 'wp-debug-toolkit'); ?>
        </h1>
        <div class="wp-debug-toolkit-version">
            <?php echo esc_html__('Version', 'wp-debug-toolkit'); ?>: <?php echo WP_DEBUG_TOOLKIT_VERSION; ?>
        </div>
    </div>