<?php
/**
 * Plugin Name: WP Debug Toolkit
 * Plugin URI: https://github.com/cedricbb/wp-debug-toolkit
 * Description: A collection of tools to help debug WordPress and Elementor.
 * Version: 1.0.0
 * Author: Cedric Billard
 * Author URI: https://github.com/cedricbb
 * Text Domain: wp-debug-toolkit
 * Domain Path: /languages
 */

// Si ce fichier est appelé directement, on sort.
if (!defined('ABSPATH')) {
    exit;
}

// Définition des constantes
const WP_DEBUG_TOOLKIT_VERSION = '1.0.0';
define('WP_DEBUG_TOOLKIT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_DEBUG_TOOLKIT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_DEBUG_TOOLKIT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Classe principale du plugin
 */
class WPDebugToolkit {

    private static ?WPDebugToolkit $instance = null;

    public static function get_instance(): ?WPDebugToolkit
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->loadDependencies();
        $this->setLocale();
        $this->initializeTools();
        $this->defineAdminHooks();
    }

    private function loadDependencies(): void
    {
        // Inclure les fichiers de fonctions utilitaires
        require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'includes/functions.php';

        // Inclure les classes principales
        require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'includes/WpDebugToolkitMain.php';
        require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'includes/WpDebugAdmin.php';
        require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'includes/WpDebugAssets.php';

        // Inclure la classe d'administration
        require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'admin/WpDebugToolkitAdminPage.php';
    }

    private function setLocale(): void
    {
        // Charger les fichiers de traduction
        add_action('plugins_loaded', [$this, 'loadPluginTextdomain']);
    }

    public  function loadPluginTextdomain(): void
    {
        load_plugin_textdomain(
            'wp-debug-toolkit',
            false,
            dirname(WP_DEBUG_TOOLKIT_PLUGIN_BASENAME) . '/languages'
        );
    }

    private function initializeTools(): void
    {
        // Charger les classes des outils
        $this->loadTool('elementor-block-analyzer');
        $this->loadTool('elementor-form-analyzer');
        $this->loadTool('api-monitor');
        $this->loadTool('media-cleaner');
        $this->loadTool('hook-inspector');
        $this->loadTool('query-profiler');
        $this->loadTool('cache-inspector');
        $this->loadTool('cron-monitor)');
    }

    private function loadTool($toolName): void
    {
        $tool_file = WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'Tools/' . $toolName . '/class-' . str_replace('-', '-', $toolName) . '.php';

        if (file_exists($tool_file)) {
            require_once $tool_file;

            // Appel dynamique à la méthode d'initialisation de chaque outil
            $class_name = $this->getToolClassName($toolName);
            if (class_exists($class_name)) {
                call_user_func(array($class_name, 'init'));
            }
        }
    }

    private function getToolClassName($tool_name): array|string
    {
        // Convertir elementor-block-analyzer en Elementor_Block_Analyzer
        return str_replace(' ', '_', ucwords(str_replace('-', ' ', $tool_name)));
    }

    private function defineAdminHooks(): void
    {
        $admin = new WPDebugAdmin();

        // Ajouter le menu d'administration
        add_action('admin_menu', array($admin, 'addAdminMenu'));

        // Enregistre les assets d'administration
        add_action('admin_enqueue_scripts', array($admin, 'enqueueAdminScript'));
    }

    public static function activate()
    {
        // Activation du plugin
    }

    public static function deactivate()
    {
        // Désactivation du plugin
    }

    public static function uninstall()
    {
        // Désinstallation du plugin
    }
}

// Hooks d'activation, de désactivation et de désinstallation
register_activation_hook(__FILE__, array('WPDebugToolkit', 'activate'));
register_deactivation_hook(__FILE__, array('WPDebugToolkit', 'deactivate'));
register_uninstall_hook(__FILE__, array('WPDebugToolkit', 'uninstall'));

// Démarre le plugin
function wp_debug_toolkit(): ?WPDebugToolkit
{
    return WPDebugToolkit::get_instance();
}

// Lancer le plugin
wp_debug_toolkit();