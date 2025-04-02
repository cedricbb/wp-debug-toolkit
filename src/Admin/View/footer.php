<?php
/**
 * Pied de page des pages d'administration
 * @package WP_Debug_Toolkit
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
    <div class="wp-debug-toolkit-footer">
        <div class="wp-debug-toolkit-footer-info">
            <p>
                <?php echo esc_html__('WP Debug Toolkit', 'wp-debug-toolkit'); ?> &copy; <?php echo date('Y'); ?>
            </p>
        </div>
    </div>
</div>
