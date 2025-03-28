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

// Obtenir l'instance de l'outil et l'URL d'action
$nonce = wp_create_nonce('wp_debug_toolkit_' . $this->id . '_action');
$actionUrl = admin_url('admin-ajax.php?action=wp_debug_toolkit_analyze_elementor_blocks&nonce=' . $nonce);
?>

<div class="wrap wp-debug-toolkit-elementor-analyzer">
    <div class="elementor-analyzer-header">
        <h2><?php echo esc_html($this->getTitle()); ?></h2>
        <p class="description"><?php echo esc_html($this->getDescription()); ?></p>
    </div>

    <?php if (!ElementorHelper::isElementorActive()): ?>
        <div class="notice notice-warning">
            <p><?php _e('Elementor n\'est pas actif sur ce site. Cet outil nécessite Elementor pour fonctionner.', 'wp-debug-toolkit'); ?></p>
        </div>
    <?php else: ?>
        <div class="elementor-analyzer-content">
            <div class="elementor-analyzer-controls">
                <button id="refresh-analysis" class="button button-primary">
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
                // Initialiser les contrôles de l'interface
                $('.elementor-analyzer-content').on('click', '.widget-details-link', function(e) {
                    e.preventDefault();
                    var widgetName = $(this).data('widget');
                    var $modal = $('#elementor-widget-details-modal');

                    $modal.find('.widget-details-loading').show();
                    $modal.find('.widget-details-content').empty();

                    // Afficher la modal
                    tb_show(
                        '<?php _e('Détails du Widget', 'wp-debug-toolkit'); ?>',
                        '#TB_inline?width=800&height=600&inlineId=elementor-widget-details-modal'
                    );

                    // Charger les détails
                    $.ajax({
                        url: ajaxurl,
                        data: {
                            action: 'get_widget_details',
                            widget: widgetName,
                            nonce: '<?php echo wp_create_nonce('wp_debug_toolkit_widget_details'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $modal.find('.widget-details-loading').hide();
                                $modal.find('.widget-details-content').html(response.data.content);

                                // Si Chart.js est chargé, initialiser le graphique
                                if (typeof Chart !== 'undefined' && response.data.chartData) {
                                    var ctx = document.getElementById('widget-usage-chart').getContext('2d');
                                    new Chart(ctx, {
                                        type: 'line',
                                        data: {
                                            labels: response.data.chartData.labels,
                                            datasets: [{
                                                label: '<?php _e('Utilisation par mois', 'wp-debug-toolkit'); ?>',
                                                data: response.data.chartData.values,
                                                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                                borderColor: 'rgba(54, 162, 235, 1)',
                                                borderWidth: 1
                                            }]
                                        },
                                        options: {
                                            scales: {
                                                y: {
                                                    beginAtZero: true,
                                                    ticks: {
                                                        precision: 0
                                                    }
                                                }
                                            }
                                        }
                                    });
                                }
                            } else {
                                $modal.find('.widget-details-loading').hide();
                                $modal.find('.widget-details-content').html('<p class="error">' + response.data.message + '</p>');
                            }
                        },
                        error: function() {
                            $modal.find('.widget-details-loading').hide();
                            $modal.find('.widget-details-content').html('<p class="error"><?php _e('Erreur lors du chargement des détails', 'wp-debug-toolkit'); ?></p>');
                        }
                    });
                });

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
                    var $button = $(this);

                    $button.prop('disabled', true);
                    $button.find('.dashicons').addClass('spin');

                    $.ajax({
                        url: '<?php echo esc_url($actionUrl); ?>',
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert('<?php _e('Erreur lors de l\'analyse. Veuillez réessayer.', 'wp-debug-toolkit'); ?>');
                                $button.prop('disabled', false);
                                $button.find('.dashicons').removeClass('spin');
                            }
                        },
                        error: function() {
                            alert('<?php _e('Erreur lors de l\'analyse. Veuillez réessayer.', 'wp-debug-toolkit'); ?>');
                            $button.prop('disabled', false);
                            $button.find('.dashicons').removeClass('spin');
                        }
                    });
                });

                // Afficher/masquer les éléments
                $('.elementor-analyzer-table-container').on('click', '.show-more-elements', function(e) {
                    e.preventDefault();
                    var $container = $(this).closest('.elements-container');
                    $container.find('.hidden-element').show();
                    $(this).text('<?php _e('Voir moins', 'wp-debug-toolkit'); ?>');
                    $(this).removeClass('show-more-elements').addClass('show-less-elements');
                });

                $('.elementor-analyzer-table-container').on('click', '.show-less-elements', function(e) {
                    e.preventDefault();
                    var $container = $(this).closest('.elements-container');
                    $container.find('.hidden-element').hide();

                    var hiddenCount = $container.find('.hidden-element').length;
                    var text = hiddenCount === 1
                        ? '<?php _e('Voir 1 élément de plus', 'wp-debug-toolkit'); ?>'
                        : '<?php echo sprintf(__('Voir %s éléments de plus', 'wp-debug-toolkit'), "' + hiddenCount + '"); ?>';

                    $(this).text(text);
                    $(this).removeClass('show-less-elements').addClass('show-more-elements');
                });
            });
        </script>
    <?php endif; ?>
</div>