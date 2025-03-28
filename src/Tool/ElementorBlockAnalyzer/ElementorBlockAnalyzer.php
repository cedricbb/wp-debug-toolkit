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
                'elementor-block-analyzer-table-css',
                WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'assets/css/tools/elementor-block-analyzer/table.css',
                [],
                WP_DEBUG_TOOLKIT_VERSION . '-' . time()
            );
            wp_enqueue_style(
                'elementor-block-analyzer-widget-details-css',
                WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'assets/css/tools/elementor-block-analyzer/details.css',
                [],
                WP_DEBUG_TOOLKIT_VERSION . '-' . time()
            );

            // Enregistrement des scripts
            wp_enqueue_script(
                'elementor-block-analyzer-table-js',
                WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'assets/js/elementor-block-analyzer-table.js',
                ['jquery', 'thickbox'],
                WP_DEBUG_TOOLKIT_VERSION . '-' . time(),
                true
            );
            wp_enqueue_script(
                'elementor-block-analyzer-widget-details-js',
                WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'assets/js/elementor-block-analyzer-widget-details.js',
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
            'cc-widget-details',
            'ccWidgetVars',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('widget_details'),
                'strings' => [
                    'modalTitle' => __('Détails du Widget', 'cc-debug-tool'),
                    'loading' => __('Chargement...', 'cc-debug-tool'),
                    'error' => __('Erreur lors du chargement des détails', 'cc-debug-tool')
                ]
            ]
        );
    }

    public function ajaxGetWidgetDetails(): void
    {
        // Vérifier le nonce
        check_ajax_referer('wp_debug_toolkit_widget_details', 'nonce');

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
            'usage_history' => $this->getUsageHistory($data),
            'instance' => $widgetInstance
        ];
        // Ajouter la date de première utilisation
        $firstUseDate = $this->getFirstUseDate($data);
        $preparedData['first_usage'] = $firstUseDate ? date_i18n('F Y', $firstUseDate) : __('Aucune utilisation', 'cc-debug-tool');
        // Fusionner avec les données originales
        return array_merge($data, $preparedData);
    }

    /**
     * Obtient la date de première utilisation
     */
    private function getFirstUseDate(array $data): ?int
    {
        $dates = [];

        foreach (['posts', 'templates', 'popups'] as $type) {
            if (!empty($data[$type])) {
                foreach (array_keys($data[$type]) as $postId) {
                    $postTime = get_post_time('U', false, $postId);
                    if ($postTime) {
                        $dates[] = $postTime;
                    }
                }
            }
        }

        if (!empty($data['theme_elements'])) {
            foreach (array_keys($data['theme_elements']) as $elementId) {
                $elementTime = get_post_time('U', false, $elementId);
                if ($elementTime) {
                    $dates[] = $elementTime;
                }
            }
        }

        return !empty($dates) ? min($dates) : null;
    }

    /**
     * Obtient l'historique d'utilisation
     */
    private function getUsageHistory(array $data): array
    {
        $usageByMonth = DateHelper::initializeLast12Months();
        $currentYear = date('Y');
        $currentMonth = date('m');

        foreach (['posts', 'templates', 'popups'] as $type) {
            if (!empty($data[$type])) {
                foreach (array_keys($data[$type]) as $postId) {
                    $postDate = get_the_date('Y-m', $postId);
                    if ($postDate) {
                        $year = substr($postDate, 0, 4);
                        $month = substr($postDate, 5, 2);

                        if (DateHelper::isWithinLast12Months($year, $month, $currentYear, $currentMonth)
                            && isset($usageByMonth[$postDate])) {
                            $usageByMonth[$postDate]++;
                        }
                    }
                }
            }
        }

        if (!empty($data['theme_elements'])) {
            foreach (array_keys($data['theme_elements']) as $elementId) {
                $elementDate = get_the_date('Y-m', $elementId);
                if ($elementDate) {
                    $year = substr($elementDate, 0, 4);
                    $month = substr($elementDate, 5, 2);

                    if (DateHelper::isWithinLast12Months($year, $month, $currentYear, $currentMonth)
                        && isset($usageByMonth[$elementDate])) {
                        $usageByMonth[$elementDate]++;
                    }
                }
            }
        }

        return $this->formatUsageHistory($usageByMonth);
    }

    /**
     * Formate l'historique d'utilisation
     */
    private function formatUsageHistory(array $usageByMonth): array
    {
        $formattedHistory = [];
        foreach ($usageByMonth as $yearMonth => $count) {
            $timestamp = strtotime($yearMonth . '-01');
            $formattedMonth = date_i18n('M Y', $timestamp);
            $formattedHistory[$formattedMonth] = $count;
        }
        return $formattedHistory;
    }

    public function renderContent(): void
    {
        $widgets = $this->analyzer->getElementorWidgets();
        $table = new ElementorWidgetsTable($widgets);
        $table->prepare_items();

        ?>
        <div class="wrap">
            <form method="post">
                <?php $table->display(); ?>
            </form>
        </div>
        <?php

        // Inclure la vue de l'outil
        include WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'src/Tool/ElementorBlockAnalyzer/View/content.php';
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
