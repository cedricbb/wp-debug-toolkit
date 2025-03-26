<?php
/**
 * Classe pour gérer les assets (CSS/JS) du plugin
 * @package WP_Debug_Toolkit
 */

namespace WPDebugToolkit\Core;

/**
 * Classe pour gérer les assets (CSS/JS) du plugin
 */
class AssetsManager
{

    /**
     * Initialiser les hooks
     */
    public function init(): void
    {
        // Enregistrer les styles et scripts d'administration
        add_action('admin_enqueue_scripts', [$this, 'registerAdminAssets']);
        // Enregistrer les styles et scripts du front-end (si nécessaire)
        add_action('wp_enqueue_scripts', [$this, 'registerFrontAssets']);
    }

    /**
     * Enregistrer les styles et scripts d'administration
     */
    public function registerAdminAssets(string $hook): void
    {
        // Vérifier si nous sommes sur une page du plugin
        if (!str_contains($hook, 'wp-debug-toolkit')) {
            return;
        }

        // Enregistrer et charger le CSS principal
        wp_register_style(
            'wp-debug-toolkit-admin',
            WP_DEBUG_TOOLKIT_PLUGIN_URL . 'assets/css/admin.css',
            [],
            WP_DEBUG_TOOLKIT_VERSION
        );

        // Enregistrer et charger le JS principal
        wp_register_script(
            'wp-debug-toolkit-admin',
            WP_DEBUG_TOOLKIT_PLUGIN_URL . 'assets/js/admin.js',
            [],
            WP_DEBUG_TOOLKIT_VERSION,
            true
        );

        // Localiser le script avec des données
        wp_localize_script(
            'wp-debug-toolkit-admin',
            'wpDebugToolkit',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp-debug-toolkit-nonce'),
                'loading' => __('Chargement...', 'wp-debug-toolkit'),
                'error' => __('Une erreur s\'est produite. Veuillez réessayer.', 'wp-debug-toolkit')
            ]
        );
    }

    /**
     * Enregistrer les styles et scripts du front-end (si nécessaire)
     */
    public function registerFrontAssets(): void
    {
        // Cette méthode peut rester vide si les outils n'ont pas besoin de code côté front-end
    }

    /**
     * Enregistrer et charger le CSS d'un outil
     * @param string $toolName Nom de l'outil
     * @return string Handle du CSS
     */
    public function enqueueToolAssets(string $toolName): void
    {
        $cssPath = WP_DEBUG_TOOLKIT_PLUGIN_URL . 'assets/css/tools/' . $toolName . '.css';
        $jsPath = WP_DEBUG_TOOLKIT_PLUGIN_URL . 'assets/js/tools/' . $toolName . '.js';

        // Enregistrer et charger le CSS spécifique à l'outil
        wp_enqueue_style(
            'wp-debug-toolkit-' . $toolName,
            $cssPath,
            ['wp-debug-toolkit-common'],
            WP_DEBUG_TOOLKIT_VERSION
        );

        // Enregistrer et charger le JS spécifique à l'outil
        wp_enqueue_script(
            'wp-debug-toolkit-' . $toolName,
            $jsPath,
            ['wp-debug-toolkit-admin'],
            WP_DEBUG_TOOLKIT_VERSION,
            true
        );
    }
}