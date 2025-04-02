<?php

namespace WPDebugToolkit\Tool\ElementorBlockAnalyzer;

use Elementor\Plugin;
use WPDebugToolkit\Tool\AbstractTool;
use WPDebugToolkit\Util\DateHelper;
use WPDebugToolkit\Util\ElementorHelper;
use WPDebugToolkit\Util\StatsHelper;

class ElementorBlockAnalyzer extends AbstractTool
{
    private ElementorElementsAnalyzer $analyzer;

    public function __construct()
    {
        parent::__construct();
        $this->analyzer = new ElementorElementsAnalyzer();
    }
    protected function registerHooks(): void
    {
        // Enregistrer le hook pour les actions AJAX
        add_action('wp_ajax_wp_debug_toolkit_analyze_elementor_blocks', array($this, 'analyzeElementorBlocks'));
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_get_widget_details', [$this, 'ajaxGetWidgetDetails']);
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

    public function enqueueAssets(string $hook): void
    {
        if (str_contains($hook, 'wp-debug-toolkit') || $hook === 'toplevel_page_wp-debug-toolkit') {
            // Enqueue CSS
            wp_enqueue_style(
                'elementor-block-analyzer-css',
                WP_DEBUG_TOOLKIT_PLUGIN_URL . 'assets/css/tools/ElementorBlockAnalyzer/elementor-block-analyzer.css',
                [],
                WP_DEBUG_TOOLKIT_VERSION . '-' . time()
            );
            wp_enqueue_style(
                'elementor-block-analyzer-widget-details-css',
                WP_DEBUG_TOOLKIT_PLUGIN_URL . 'assets/css/tools/ElementorBlockAnalyzer/elementor-block-analyzer-widget-details.css',
                [],
                WP_DEBUG_TOOLKIT_VERSION . '-' . time()
            );

            // Enregistrement des scripts
            wp_enqueue_script(
                'elementor-block-analyzer-js',
                WP_DEBUG_TOOLKIT_PLUGIN_URL . 'assets/js/tools/ElementorBlockAnalyzer/elementor-block-analyzer.js',
                ['jquery', 'thickbox'],
                WP_DEBUG_TOOLKIT_VERSION . '-' . time(),
                true
            );
            wp_enqueue_script(
                'elementor-block-analyzer-widget-detail',
                WP_DEBUG_TOOLKIT_PLUGIN_URL . 'assets/js/tools/ElementorBlockAnalyzer/elementor-block-analyzer-widget-detail.js',
                ['jquery', 'thickbox', 'postbox'],
                WP_DEBUG_TOOLKIT_VERSION . '-' . time(),
                true
            );

            // ThickBox
            add_thickbox();
            // Postbox
            wp_enqueue_script('postbox');
            wp_enqueue_script('common');

            // Chart.js
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js',
                [],
                '3.7.0',
                true
            );

            $this->localizeScripts();
        }
    }

    private function localizeScripts(): void
    {
        // Localisation pour JavaScript
        wp_localize_script(
            'elementor-block-analyzer-widget-detail',
            'WPDebugWidgetVars',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('widget_details'),
                'strings' => [
                    'modalTitle' => __('Détails du Widget', 'wp-debug-toolkit'),
                    'loading' => __('Chargement...', 'wp-debug-toolkit'),
                    'error' => __('Erreur lors du chargement des détails', 'wp-debug-toolkit')
                ]
            ]
        );
    }

    public function ajaxGetWidgetDetails(): void
    {
        // Vérifier le nonce
        check_ajax_referer('widget_details', 'nonce');

        $widgetName = sanitize_text_field($_GET['widget'] ?? '');

        if (empty($widgetName)) {
            wp_send_json_error(['message' => 'Nom du widget manquant']);
            return;
        }

        $widgets = $this->analyzer->getElementorWidgets();

        if (!isset($widgets[$widgetName])) {
            wp_send_json_error(['message' => 'Widget non trouvé']);
            return;
        }

        $widgetData = $this->prepareWidgetData($widgets[$widgetName], $widgetName);

        ob_start();
        include WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'src/Tool/ElementorBlockAnalyzer/View/widget-details.php';
        $content = ob_get_clean();

        wp_send_json_success([
            'content' => $content,
            'widgetData' => $widgetData,
            'chartData' => [
                'labels' => array_keys($widgetData['usage_history']),
                'values' => array_values($widgetData['usage_history'])
            ]
        ]);
    }

    private function prepareWidgetData(array $data, string $widgetName): array
    {
        // Récupérer l'instance du widget Elementor
        $widgetInstance = null;
        if (class_exists('\Elementor\Plugin')) {
            $widgetManager = Plugin::instance()->widgets_manager;
            $widgetInstance = $widgetManager->get_widget_types($widgetName);
        }
        // Préparer les données de base
        $preparedData = [
            'name' => $widgetName,
            'icon' => ElementorHelper::getWidgetIcon($widgetName),
            'total_uses' => StatsHelper::calculateTotalUses($data),
            'average_uses' => StatsHelper::calculateAverageUsesPerPage($data),
            'usage_history' => DateHelper::getUsageHistory($data),
            'instance' => $widgetInstance
        ];
        // Ajouter la date de première utilisation
        $firstUseDate = DateHelper::getFirstUseDate($data);
        $preparedData['first_usage'] = $firstUseDate ? date_i18n('F Y', $firstUseDate) : __('Aucune utilisation', 'wp-debug-toolkit');
        // Fusionner avec les données originales
        return array_merge($data, $preparedData);
    }

    public function renderContent(): void
    {
        try {
            // Préparer les données pour la vue
            $widgets = $this->analyzer->getElementorWidgets();
            $table = new ElementorWidgetsTable($widgets);
            $table->prepare_items();

            // Passer les variables à la vue (table et autres données)
            include WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'src/Tool/ElementorBlockAnalyzer/View/content.php';
        } catch (\Throwable $e) {
            // Gérer l'erreur
            echo '<div class="notice notice-error"><p>';
            echo 'Erreur lors de l\'analyse des widgets Elementor: ' . esc_html($e->getMessage());
            echo '</p></div>';
        }
    }
}
