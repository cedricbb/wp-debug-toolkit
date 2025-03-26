<?php
/**
 * Classe pour gérer les assets (CSS/JS) du plugin
 * @package WP_Debug_Toolkit
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour gérer les assets (CSS/JS) du plugin
 */
class WPDebugAssets {

    private static $instance = null;

    /**
     * Constructeur de la classe
     */
    public function __construct() {
        // initialiser les hooks
        $this->initHooks();
    }

    /**
     * Initialiser les hooks
     */
    public function initHooks(): void
    {
        // Enregistrer les styles et scripts d'administration
        add_action('admin_enqueue_scripts', [$this, 'registerAdminAssets']);
        // Enregistrer les styles et scripts du front-end (si nécessaire)
        add_action('wp_enqueue_scripts', [$this, 'registerFrontAssets']);
    }

    /**
     * Enregistrer les styles et scripts d'administration
     */
    public function registerAdminAssets(): void
    {
        // Enregistrer CSS commun
        wp_register_style(
            'wp-debug-toolkit-admin',
            WP_DEBUG_TOOLKIT_PLUGIN_URL . 'assets/css/admin.css',
            [],
            WP_DEBUG_TOOLKIT_VERSION
        );

        // Enregistrer JS commun
        wp_register_script(
            'wp-debug-toolkit-admin',
            WP_DEBUG_TOOLKIT_PLUGIN_URL . 'assets/js/admin.js',
            [],
            WP_DEBUG_TOOLKIT_VERSION,
            true
        );

        // Enregistrer le CSS du tableau de bord
        wp_register_style(
            'wp-debug-toolkit-dashboard',
            WP_DEBUG_TOOLKIT_PLUGIN_URL . 'assets/css/dashboard.css',
            [],
            WP_DEBUG_TOOLKIT_VERSION
        );

        // Enregistrer le JS du tableau de bord
        wp_register_script(
            'wp-debug-toolkit-dashboard',
            WP_DEBUG_TOOLKIT_PLUGIN_URL . 'assets/js/dashboard.js',
            [],
            WP_DEBUG_TOOLKIT_VERSION,
            true
        );
    }

    /**
     * Enregistrer les styles et scripts du front-end (si nécessaire)
     */
    public function registerFrontAssets(): void
    {
        // Cette méthode peut rester vide si les outils n'ont pas besoin de code côté front-end
        // ou vous pouvez enregistrer des assets spécifiques ici si nécessaire.
    }

    /**
     * Enregistrer et charger le CSS d'un outil
     * @param string $toolName Nom de l'outil
     * @return string Handle du CSS
     */
    public function enqueueToolCss(string $toolName): string
    {
        $toolCssPath = WP_DEBUG_TOOLKIT_PLUGIN_URL . 'Tools/' . $toolName . '/assets/css/' . $toolName . '.css';
        $toolCssHandle = 'wp-debug-toolkit-' . $toolName;

        // Enregistrer et charger le CSS
        wp_enqueue_style(
            $toolCssHandle,
            $toolCssPath,
            ['wp-debug-toolkit-common'],
            WP_DEBUG_TOOLKIT_VERSION
        );

        return $toolCssHandle;
    }

    /**
     * Enregistrer et charger le JS d'un outil
     * @param string $toolName Nom de l'outil
     * @return string Handle du JS
     */
    public function enqueueToolJs(string $toolName): string
    {
        $toolJsPath = WP_DEBUG_TOOLKIT_PLUGIN_URL . 'Tools/' . $toolName . '/assets/js/' . $toolName . '.js';
        $toolJsHandle = 'wp-debug-toolkit-' . $toolName;

        // Enregistrer et charger le JS
        wp_enqueue_script(
            $toolJsHandle,
            $toolJsPath,
            ['wp-debug-toolkit-common'],
            WP_DEBUG_TOOLKIT_VERSION,
            true
        );

        return $toolJsHandle;
    }

    /**
     * Localiser un script avec des données
     * @param string $handle Handle du script
     * @param string $objectName Nom de l'objet JS
     * @param array $data Données à localiser
     */
    public function localizeScript(string $handle, string $objectName, array $data): void
    {
        wp_localize_script(
            $handle,
            $objectName,
            $data
        );
    }

    public static function getInstance(): WPDebugAssets
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}