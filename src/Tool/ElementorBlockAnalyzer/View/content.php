<?php
/**
 * Vue de l'analyseur de blocs Elementor
 * @package WP_Debug_Toolkit
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wp-debug-toolkit-tool-content elementor-block-analyzer">
    <div class="wp-debug-toolkit-welcome">
        <h2><?php echo esc_html__('Analyseur de blocs Elementor', 'wp-debug-toolkit'); ?></h2>
        <p class="description">
            <?php echo esc_html__('Cet outil analyse l\'utilisation des widgets Elementor sur votre site et vous aide à comprendre quels widgets sont les plus utilisés.', 'wp-debug-toolkit'); ?>
    </div>

    <div class="wp-debug-toolkit-card">
        <div class="wp-debug-toolkit-card-header">
            <h3><?php echo esc_html__('Analyse des widgets', 'wp-debug-toolkit'); ?></h3>
        </div>
        <div class="wp-debug-toolkit-card-body">
            <div class="wp-debug-toolkit-form-row">
                <button id="start-analysis" class="button button-crayola">
                    <?php echo esc_html__('Lancer l\'analyse', 'wp-debug-toolkit'); ?>
                </button>
                <span class="spinner"></span>
            </div>

            <div id="analysis-results" style="display:none;">
                <div class="wp-debug-toolkit-progress">
                    <progress id="analysis-progress" value="0" max="100"></progress>
                    <div class="wp-debug-toolkit-progress-status">
                        <?php echo esc_html__('Analyse en cours...', 'wp-debug-toolkit'); ?>
                    </div>
                </div>

                <div id="results-container" style="display:none;">
                    <h4><?php echo esc_html__('Résultats de l\'analyse', 'wp-debug-toolkit'); ?></h4>
                    <table class="wp-debug-toolkit-table" id="widgets-table">
                        <thead class="wp-debug-toolkit-table" id="widgets-table">
                        <tr>
                            <th><?php echo esc_html__('Widget', 'wp-debug-toolkit'); ?></th>
                            <th><?php echo esc_html__('Nombre d\'utilisations', 'wp-debug-toolkit'); ?></th>
                            <th><?php echo esc_html__('Pages utilisant ce widget', 'wp-debug-toolkit'); ?></th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

