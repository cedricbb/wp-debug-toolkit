<?php
/**
 * Plugin Name: WP Debug Toolkit
 * Plugin URI: https://github.com/cedricbb/wp-debug-toolkit
 * Description: A collection of tools to help debug WordPress and Elementor.
 * Version: 1.5.3
 * Author: Cedric Billard
 * Author URI: https://github.com/cedricbb
 * Text Domain: wp-debug-toolkit
 * Domain Path: /languages
 * Requires PHP: 7.4
 */

// Si ce fichier est appelé directement, on sort.
if (!defined('ABSPATH')) {
    exit;
}

// Définition des constantes
const WP_DEBUG_TOOLKIT_VERSION = '1.5.3';
define('WP_DEBUG_TOOLKIT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_DEBUG_TOOLKIT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_DEBUG_TOOLKIT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Chargement de l'autoloader Composer
if (file_exists(WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'vendor/autoload.php';
} else {
    // Afficher un message d'erreur si l'autoloader n'est pas trouvé
    add_action('admin_notices', function () {
        ?>
        <div class="notice notice-error">
            <p><?php _e('WP Debug Toolkit requires Composer autoloader. Please run <code>composer install</code> in the plugin directory.', 'wp-debug-toolkit'); ?></p>
        </div>
        <?php
    });
    return;
}

// Utiliser les namespaces pour initialiser le plugin
use WPDebugToolkit\Core\Plugin;

// Hooks d'activation, de désactivation et de désinstallation
register_activation_hook(__FILE__, array('WPDebugToolkit\Core\Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('WPDebugToolkit\Core\Plugin', 'deactivate'));

// Démarrer le plugin
add_action('plugins_loaded', function () {
    Plugin::get_instance()->init();
});
