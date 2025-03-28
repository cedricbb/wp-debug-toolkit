<?php

namespace WPDebugToolkit\Tool\ElementorBlockAnalyzer;

use WPDebugToolkit\Tool\AbstractTool;
use WPDebugToolkit\Util\ElementorHelper;

class ElementorBlockAnalyzer extends AbstractTool
{

    protected function registerHooks(): void
    {
        // Enregistrer le hook pour les actions AJAX
        add_action('wp_ajax_wp_debug_toolkit_analyze_elementor_blocks', array($this, 'analyzeElementorBlocks'));
    }

    protected function getDefaultId(): string
    {
        return 'elementor-block-analyzer';
    }

    protected function getDefaultTitle(): string
    {
        return __('Analyseur de blocs Elementor', 'wp-debug-toolkit');
    }

    protected function getDefaultDescription(): string
    {
        return __('Analyse l\'utilisation des widgets Elementor sur le site', 'wp-debug-toolkit');
    }

    protected function getDefaultIcon(): string
    {
        return 'dashicons-welcome-widgets-menus';
    }

    public function renderContent(): void
    {
        // Vérifier si Elementor est actif
        if (!ElementorHelper::isElementorActive()) {
            $this->showNotice(
                __('Elementor n\'est pas activé. Veuillez activer Elementor pour utiliser cet outil.', 'wp-debug-toolkit'),
                'warning'
            );
            return;
        }

        // Inclure la vue de l'outil
        $viewPath = WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'src/Tool/ElementorBlockAnalyzer/View/content.php';

        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo '<div class="notice notice-error"><p>';
            echo __('Impossible de charger la vue de l\'outil. Fichier non trouvé : ', 'wp-debug-toolkit') . $viewPath;
            echo '</p></div>';
        }
    }

    public function analyzeElementorBlock(): void
    {
        // Vérifie le nonce
        check_ajax_referer('wp_debug_toolkit_' . $this->id . '_action', 'nonce');

        // Analyser les blocs Elementor
        $blocks = ElementorHelper::getAllElementorWidgets();
        $usage = ElementorHelper::analyzeWidgetUsage();

        // Retourner les résultats
        wp_send_json_success(array(
            'blocks' => $blocks,
            'usage' => $usage
        ));
    }
}
