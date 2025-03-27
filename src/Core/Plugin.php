<?php

declare(strict_types=1);

namespace WPDebugToolkit\Core;

use WPDebugToolkit\Admin\AdminManager;
use WPDebugToolkit\Tool\ToolManager;
use WPDebugToolkit\Admin\DashboardCustomizer;
class Plugin
{
    private static ?Plugin $instance = null;
    private AssetsManager $assetsManager;
    private AdminManager $adminManager;
    private ToolManager $toolManager;
    private $dashboardCustomizer;

    private function __construct()
    {
        // Initialisation des gestionnaires
        $this->assetsManager = new AssetsManager();
        $this->adminManager = new AdminManager();
        $this->toolManager = new ToolManager();
        $this->dashboardCustomizer = new DashboardCustomizer();
    }

    public function init(): void
    {
        // Charger les traductions
        add_action('plugins_loaded', [$this, 'loadTextDomain']);
        $this->toolManager->init();
        $this->adminManager->init();
        $this->assetsManager->init();
        $this->dashboardCustomizer->init();
        // Ajouter le lien Paramètres dans la liste des plugins
        add_filter('plugin_action_links_' . WP_DEBUG_TOOLKIT_PLUGIN_BASENAME, [$this, 'addSettingsLink']);
    }

    public function loadTextDomain(): void
    {
        load_plugin_textdomain(
            'wp-debug-toolkit',
            false,
            dirname(WP_DEBUG_TOOLKIT_PLUGIN_BASENAME) . '/languages');
    }

    public function addSettingsLink(array $links): array
    {
        $settingsLink = '<a href="' . admin_url('admin.php?page=wp-debug-toolkit&tab=settings') . '">' . __('Paramètres', 'wp-debug-toolkit') . '</a>';
        array_unshift($links, $settingsLink);
        return $links;
    }

    public static function activate(): void
    {
        // Crée les options par défaut si elles n'existent pas
        if (!get_option('wp_debug_toolkit_settings')) {
            update_option('wp_debug_toolkit_settings', [
                'access_level' => 'manage_options',
                'enable_logging' => false,
                'developer_mode' => false,
            ]);
        }

        // Créer la structure de dossiers pour les logs si la journalisation est activée
        $settings = get_option('wp_debug_toolkit_settings');
        if (isset($settings['enable_logging']) && $settings['enable_logging']) {
            self::createLogsDirectory();
        }

        // Vider le cache des permaliens
        flush_rewrite_rules();
    }

    public static function deactivate(): void
    {
        // Vider le cache des permaliens
        flush_rewrite_rules();
    }

    private static function createLogsDirectory(): void
    {
        $uploadDir = wp_upload_dir();
        $logsDir = $uploadDir['basedir'] . '/wp-debug-toolkit-logs';
        if (!file_exists($logsDir)) {
            wp_mkdir_p($logsDir);

            // Créer un fichier .htaccess pour protéger le repertoire
            $htaccessContent = "# Deny access to all files\n";
            $htaccessContent .= "<FilesMatch \".*\">\n";
            $htaccessContent .= "Order Allow,Deny\n";
            $htaccessContent .= "Deny from all\n";
            $htaccessContent .= "</FilesMatch>\n";

            @file_put_contents($logsDir . '/.htaccess', $htaccessContent);

            // Créer un fichier index.html pour sécurité supplémentaire
            @file_put_contents($logsDir . '/index.php', '<?php // Silence is golden.');
        }
    }

    public function getAssetsManager(): AssetsManager
    {
        return $this->assetsManager;
    }

    public function getAdminManager(): AdminManager
    {
        return $this->adminManager;
    }

    public function getToolManager(): ToolManager
    {
        return $this->toolManager;
    }

    public function getDashboardCustomizer(): DashboardCustomizer
    {
        return $this->dashboardCustomizer;
    }

    public static function get_instance(): Plugin
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}