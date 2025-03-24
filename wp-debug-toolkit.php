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
define('WP_DEBUG_TOOLKIT_VERSION', '1.0.0');
define('WP_DEBUG_TOOLKIT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_DEBUG_TOOLKIT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_DEBUG_TOOLKIT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Classe principale du plugin
 */
class WP_Debug_Toolkit {

    private static $instance = null;

    public static function get_instance() 
    {
        if (null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() 
    {
        $this->load_dependencies();
        $this->set_locale();
        $this->initialize_tools();
        $this->define_admin_hooks();
    }

    private function load_dependencies()
    {
        // Inclure les fichiers de fonctions utilitaires
        require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'includes/functions.php';

        // Inclure les classes principales
        require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'includes/class-wp-debug-toolkit.php';
        require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'includes/class-wp-debug-admin.php';
        require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'includes/class-wp-debug-assets.php';

        // Inclure la classe d'administration
        require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'admin/class-admin-page.php';
    }

    private function set_locale()
    {
        // Charger les fichiers de traduction
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
    }

    public  function load_plugin_textdomain()
    {
        load_plugin_textdomain(
            'wp-debug-toolkit',
            false,
            dirname(WP_DEBUG_TOOLKIT_PLUGIN_BASENAME) . '/languages'
        );
    }

    private function initialize_tools()
    {
        // Charger les classes des outils
        $this->load_tool('elementor-block-analyzer');
        $this->load_tool('elementor-form-analyzer');
        $this->load_tool('api-monitor');
        $this->load_tool('media-cleaner');
        $this->load_tool('hook-inspector');
        $this->load_tool('query-profiler');
        $this->load_tool('cache-inspector');
        $this->load_tool('cron-monitor)');
    }

    private function load_tool($tool_name)
    {
        $tool_file = WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'tools/' . $tool_name . '/class-' . str_replace('-', '-', $tool_name) . '.php';

        if (file_exists($tool_file)) {
            require_once $tool_file;

            // Appel dynamique à la méthode d'initialisation de chaque outil
            $class_name = $this->get_tool_class_name($tool_name);
            if (class_exists($class_name)) {
                call_user_func(array($class_name, 'init'));
            }
        }
    }

    private function get_tool_class_name($tool_name)
    {
        // Convertir elementor-block-analyzer en Elementor_Block_Analyzer
        $class_suffix = str_replace(' ', '_', ucwords(str_replace('-', ' ', $tool_name)));
        return $class_suffix;
    }

    private function define_admin_hooks()
    {
        $admin = new WP_Debug_Admin();

        // Ajouter le menu d'administration
        add_action('admin_menu', array($admin, 'add_admin_menu'));

        // Enregistre les assets d'administration
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_admin_assets'));
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
register_activation_hook(__FILE__, array('WP_Debug_Toolkit', 'activate'));
register_deactivation_hook(__FILE__, array('WP_Debug_Toolkit', 'deactivate'));
register_uninstall_hook(__FILE__, array('WP_Debug_Toolkit', 'uninstall'));

// Démarre le plugin
function wp_debug_toolkit()
{
    return WP_Debug_Toolkit::get_instance();
}

// Lancer le plugin
wp_debug_toolkit();
