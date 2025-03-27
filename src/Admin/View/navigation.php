<?php
/**
 * Navigation entre les onglets
 * @package WP_Debug_Toolkit
 */

if (!defined('ABSPATH')) {
    exit;
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

