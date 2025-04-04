<?php
/**
 * Vue principale pour l'analyseur de blocs Elementor
 *
 * @package WP_Debug_Toolkit
 */

// Protection contre l'accès direct
use WPDebugToolkit\Util\ElementorHelper;

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap wp-debug-toolkit-elementor-analyzer">
    <div class="elementor-analyzer-header">
        <p class="description"><?php echo esc_html($this->getDescription()); ?></p>
    </div>

    <?php if (!ElementorHelper::isElementorActive()): ?>
        <div class="notice notice-warning">
            <p><?php _e('Elementor n\'est pas actif sur ce site. Cet outil nécessite Elementor pour fonctionner.', 'wp-debug-toolkit'); ?></p>
        </div>
    <?php else: ?>
        <div class="elementor-analyzer-content">
            <div class="elementor-analyzer-controls">
                <button id="refresh-analysis" class="button button-crayola">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Actualiser l\'analyse', 'wp-debug-toolkit'); ?>
                </button>

                <div class="elementor-analyzer-filters">
                    <label for="filter-by-usage">
                        <?php _e('Filtrer par utilisation:', 'wp-debug-toolkit'); ?>
                    </label>
                    <select id="filter-by-usage">
                        <option value="all"><?php _e('Tous', 'wp-debug-toolkit'); ?></option>
                        <option value="high"><?php _e('Utilisation élevée (10+)', 'wp-debug-toolkit'); ?></option>
                        <option value="medium"><?php _e('Utilisation moyenne (5-10)', 'wp-debug-toolkit'); ?></option>
                        <option value="low"><?php _e('Utilisation faible (1-5)', 'wp-debug-toolkit'); ?></option>
                    </select>
                </div>
            </div>

            <div class="elementor-analyzer-table-container">
                <form method="post">
                    <?php $table->display(); ?>
                </form>
            </div>

            <div id="elementor-widget-details-modal" style="display:none;">
                <div class="widget-details-container">
                    <div class="widget-details-loading">
                        <span class="spinner is-active"></span>
                        <p><?php _e('Chargement des détails...', 'wp-debug-toolkit'); ?></p>
                    </div>
                    <div class="widget-details-content"></div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Filtrage de la table
                $('#filter-by-usage').on('change', function() {
                    var value = $(this).val();
                    if (value === 'all') {
                        $('.wp-list-table tr').show();
                    } else {
                        $('.wp-list-table tr').hide();
                        $('.wp-list-table tr:first-child').show(); // Conserver l'en-tête

                        if (value === 'high') {
                            $('.wp-list-table .high-usage').closest('tr').show();
                        } else if (value === 'medium') {
                            $('.wp-list-table .medium-usage').closest('tr').show();
                        } else if (value === 'low') {
                            $('.wp-list-table .low-usage').closest('tr').show();
                        }
                    }
                });

                // Actualiser l'analyse
                $('#refresh-analysis').on('click', function(e) {
                    e.preventDefault();
                    location.reload();
                });
            });
        </script>
    <?php endif; ?>
</div>