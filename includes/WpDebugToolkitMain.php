<?php
/**
 * Classe principale du plugin WP Debug Toolkit
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe principale qui coordonne toutes les fonctionnalités
 */
class WPDebugToolkitMain {

    private static $instance = null;
    private $admin;
    private $assets;
    private $activeTools = [];

    /**
     * Constructeur
     */
    private function __construct()
    {
        // Initialiser les propriétés
    }

    /**
     * Initialiser le plugin
     */
    public function init(): void
    {
        // Charger les instances des classes principales
        $this->loadCoreInstance();
        // Charger les outils actifs
        $this->loadActiveTools();
        // Initialiser les hooks
        $this->initHooks();
    }

    /**
     * Charger les instances des classes principales
     */
    private function loadCoreInstance(): void
    {
        // Charger l'instance Admin
        $this->admin = WPDebugAdmin::getInstance();
        // Charger l'instance Assets
        $this->assets = WPDebugAssets::getInstance();
    }

    /**
     * Charger les outils actifs
     */
    private function loadActiveTools(): void
    {
        // Récupérer les paramètres des outils depuis les options
        $toolSettings = get_option('wp_debug_toolkit_tools', []);

        // Par défaut, activer tous les outils si aucun paramètre n'est défini
        if (empty($toolSettings)) {
            $this->activeTools = [
                'elementor-block-analyzer' => true,
                'elementor-form-analyzer' => true,
                'api-monitor' => true,
                'media-cleaner' => true,
                'hook-inspector' => true,
                'query-profiler' => true,
                'cache-inspector' => true,
                'cron-monitor' => true,
            ];
        } else {
            $this->activeTools = $toolSettings;
        }
    }

    /**
     * Initialiser les hooks WordPress
     */
    private function initHooks(): void
    {
        // Hooks d'activation/désactivation
        register_activation_hook(WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'wp-debug-toolkit.php', [$this, 'activate']);
        register_deactivation_hook(WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'wp-debug-toolkit.php', [$this, 'deactivate']);
        // Ajouter le lien Paramètres dans la liste des plugins
        add_filter('plugin_action_links_' . WP_DEBUG_TOOLKIT_PLUGIN_BASENAME, [$this, 'addSettingsLink']);
        // Initialiser les traductions
        add_action('plugins_loaded', [$this, 'loadTextDomain']);
    }

    /**
     * Activer le plugin
     */
    public function activate(): void
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
            $this->createLogsDirectory();
        }

        // Vider le cache des permaliens
        flush_rewrite_rules();
    }

    /**
     * Désactiver le plugin
     */
    public function deactivate(): void
    {
        // Vider le cache des permaliens
        flush_rewrite_rules();
    }

    /**
     * Ajouter le lien Paramètres dans la liste des plugins
     */
    public function addSettingsLink(array $links): array
    {
        $settingsLink = '<a href="' . admin_url('admin.php?page=wp-debug-toolkit&tab=settings') . '">' . __('Paramètres', 'wp-debug-toolkit') . '</a>';
        array_unshift($links, $settingsLink);
        return $links;
    }

    /**
     * Charger les fichiers de traductions
     */
    public function loadTextDomain(): void
    {
        load_plugin_textdomain('wp-debug-toolkit', false, basename(WP_DEBUG_TOOLKIT_PLUGIN_DIR) . '/languages');
    }

    /**
     * Créer le repertoire pour les logs
     */
    private function createLogsDirectory(): void
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

    /**
     * Vérifier si un outil est actif
     * @param string $toolName Nom de l'outil
     * @return bool true si l'outil est actif, sinon false
     */
    public function isToolActive(string $toolName): bool
    {
        return isset($this->activeTools[$toolName]) && $this->activeTools[$toolName];
    }

    /**
     * Activer ou désactiver un outil
     * @param string $toolName Nom de l'outil
     * @param bool $active true pour activer, false pour désactiver
     */
    public function setToolActive(string $toolName, bool $active = true): void
    {
        $this->activeTools[$toolName] = (bool) $active;
        update_option('wp_debug_toolkit_tools', $this->activeTools);
    }

    /**
     * Obtenir l'instance Admin
     */
    public function getAdmin(): WPDebugAdmin
    {
        return $this->admin;
    }

    /**
     * Obtenir l'instance Assets
     */
    public function getAssets(): WPDebugAssets
    {
        return $this->assets;
    }

    /**
     * Obtenir l'instance principale
     */
    public static function getInstance(): WPDebugToolkitMain
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}