<?php
/**
 * API Monitor tool
 * @package WPDebugToolkit
 */

namespace WPDebugToolkit\Tool\ApiMonitor;

use WPDebugToolkit\Util\DatabaseManager;
use WPDebugToolkit\Tool\ApiMonitor\ApiAnalyzer;
use WPDebugToolkit\Tool\ApiMonitor\ApiLogger;

use WPDebugToolkit\Tool\AbstractTool;

/**
 * API Monitor tool
 */
class ApiMonitor extends AbstractTool
{
    private DatabaseManager $database;
    private ApiAnalyzer $analyzer;
    private ApiLogger $logger;

    public function __construct()
    {
        // Initialiser la base de données
        $this->database = new DatabaseManager();
        $this->analyzer = new ApiAnalyzer($this->database);
        $this->logger = new ApiLogger($this->database);

        // Appeler le constructeur parent
        parent::__construct();

        // Initialiser les hooks
        $this->registerHooks();
    }

    protected function registerHooks(): void
    {
        // Hooks pour intercepter les appels API
        add_action('rest_api_init', [$this, 'registerMonitoring']);
        add_action('rest_pre_dispatch', [$this->logger, 'logPreDispatch'], 10, 3);
        add_action('rest_post_dispatch', [$this->logger, 'logPostDispatch'], 10, 3);

        // Hooks admin
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);

        // Hooks AJAX
        add_action('wp_ajax_get_api_details', [$this, 'ajaxGetApiDetails']);
        add_action('wp_ajax_clear_api_logs', [$this, 'ajaxClearLogs']);
    }

    protected function getDefaultId(): string
    {
        return 'api-monitor';
    }

    protected function getDefaultTitle(): string
    {
        return __('API Monitor', 'wp-debug-toolkit');
    }

    protected function getDefaultDescription(): string
    {
        return __('Surveille les appels API REST entrants et sortants', 'wp-debug-toolkit');
    }

    protected function getDefaultIcon(): string
    {
        return 'dashicons-api';
    }

    public function registerMonitoring(): void
    {
        register_rest_route('wp-debug-toolkit/v1', '/monitor', [
            'methods' => 'GET',
            'callback' => [$this, 'geMonitoringStatus'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            }
        ]);
    }

    public function enqueueAssets(string $hook): void
    {
        if (str_contains($hook, 'wp-debug-toolkit') || $hook === 'toplevel_page_wp-debug-toolkit') {

        // Scripts WordPress nécessaires
        wp_enqueue_script('postbox');
        wp_enqueue_script('common');
        add_thickbox();

        wp_enqueue_style(
            'api-monitor-css',
            WP_DEBUG_TOOLKIT_PLUGIN_URL . 'assets/css/tools/ApiMonitor/api-monitor.css',
            [],
            WP_DEBUG_TOOLKIT_VERSION
        );

        wp_enqueue_script(
            'api-monitor-js',
            WP_DEBUG_TOOLKIT_PLUGIN_URL . 'assets/js/tools/ApiMonitor/api-monitor.js',
            ['jquery', 'thickbox'],
            WP_DEBUG_TOOLKIT_VERSION
        );

        $this->localizeScripts();
        }
}

    private function localizeScripts(): void
    {
        wp_localize_script('api-monitor-js', 'wpDebugToolkitApiMonitor', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('api-monitor-js'),
            'strings' => [
                'modalTitle' => __('Détails de l\'appel API', 'wp-debug-toolkit'),
                'loading' => __('Chargement...', 'wp-debug-toolkit'),
                'error' => __('Erreur lors du chargement des détails', 'wp-debug-toolkit'),
                'confirmClear' => __('Êtes-vous sûr de vouloir supprimer tous les logs ? Cette action est irréversible.', 'wp-debug-toolkit'),
                'clearSuccess' => __('Les logs ont été supprimés avec succès.', 'wp-debug-toolkit'),
                'clearError' => __('Une erreur est survenue lors de la suppression des logs.', 'wp-debug-toolkit')
            ]
        ]);
    }

    private function filterCalls(array $calls, string $endpoint = '', string $status = ''): array
    {
        if (empty($endpoint) && empty($status)) {
            return $calls;
        }

        return array_filter($calls, function($call) use ($endpoint, $status) {
            // Filtre par endpoint
            if (!empty($endpoint) && $call['endpoint'] !== $endpoint) {
                return false;
            }

            // Filtre par statut
            if (!empty($status)) {
                $statusCode = (int)$call['response_code'];
                switch ($status) {
                    case 'success':
                        if ($statusCode < 200 || $statusCode >= 300) return false;
                        break;
                    case 'redirect':
                        if ($statusCode < 300 || $statusCode >= 400) return false;
                        break;
                    case 'error':
                        if ($statusCode < 400) return false;
                        break;
                }
            }

            return true;
        });
    }

    public function ajaxGetApiDetails(): void
    {
        check_ajax_referer('api-monitor-js', '_wpnonce');

        $logId = (int)($_GET['log_id'] ?? 0);

        if ($logId === 0) {
            wp_send_json_error(['message' => 'ID de log invalide']);
            return;
        }

        $details = $this->analyzer->getCallDetails($logId);

        if (!$details) {
            wp_send_json_error(['message' => 'Log non trouvé']);
            return;
        }

        ob_start();
        require WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'src/Tool/ApiMonitor/View/details.php';
        $content = ob_get_clean();

        wp_send_json_success(['content' => $content]);
    }

    public function ajaxClearLogs(): void
    {
        try {
            // Vérifier le nonce
            check_ajax_referer('api-monitor-js', '_wpnonce');

            // Vérifier les permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error([
                    'message' => __('Vous n\'avez pas les permissions nécessaires.', 'wp-debug-toolkit')
                ]);
                return;
            }

            // Supprimer les logs
            $this->database->clearLogs();

            wp_send_json_success([
                'message' => __('Tous les logs ont été supprimés avec succès.', 'wp-debug-toolkit')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => __('Une erreur est survenue lors de la suppression des logs.', 'wp-debug-toolkit')
            ]);
        }
    }

    /**
     * Endpoint de surveillance
     */
    public function getMonitoringStatus(): \WP_REST_Response
    {
        return new \WP_REST_Response([
            'status' => 'active',
            'version' => '1.0.0',
            'last_check' => current_time('mysql'),
        ]);
    }

    public function renderContent(): void
    {
        try {
            // Récupérer les paramètres de filtre
            $endpoint_filter = isset($_GET['endpoint']) ? sanitize_text_field($_GET['endpoint']) : '';
            $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

            // Récupérer les stats générales
            $stats = $this->analyzer->getStats() ?? [
                'total_calls' => 0,
                'total_errors' => 0,
                'avg_response_time' => 0,
                'calls_today' => 0,
                'avg_response_time_24h' => 0
            ];

            // Nombre d'éléments à afficher par vue
            $limit = 10;

            // Récupérer les données avec les filtres
            $recentCalls = $this->filterCalls(
                $this->analyzer->getRecentCalls($limit),
                $endpoint_filter,
                $status_filter
            );

            $slowestCalls = $this->filterCalls(
                $this->analyzer->getSlowestCalls($limit),
                $endpoint_filter,
                $status_filter
            );

            $errorCalls = $this->filterCalls(
                $this->analyzer->getErrorCalls($limit),
                $endpoint_filter,
                $status_filter
            );

            // Récupérer la liste des endpoints pour le filtre
            $allCalls = array_merge($recentCalls, $slowestCalls, $errorCalls);
            $endpoints = array_unique(array_column($allCalls, 'endpoint'));
            sort($endpoints);

            // Afficher le template
            include WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'src/Tool/ApiMonitor/View/dashboard.php';

        } catch (\Exception $e) {
            // Valeurs par défaut en cas d'erreur
            $stats = [
                'total_calls' => 0,
                'total_errors' => 0,
                'avg_response_time' => 0,
                'calls_today' => 0,
                'avg_response_time_24h' => 0
            ];
            $recentCalls = [];
            $slowestCalls = [];
            $errorCalls = [];
            $endpoints = [];
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html(sprintf(
                        __('Erreur lors de la récupération des données : %s', 'cc-debug-tool'),
                        $e->getMessage()
                    )); ?></p>
            </div>
            <?php
            include WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'src/Tool/ApiMonitor/View/dashboard.php';
        }
    }
}
