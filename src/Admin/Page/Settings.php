<?php

namespace WPDebugToolkit\Admin\Page;

use WPDebugToolkit\Tool\ToolManager;

class Settings extends AbstractPage
{
    public function __construct()
    {
        parent::__construct('settings', __('Paramètres', 'wp-debug-toolkit'), 'dashicons-admin-settings');
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function render(): void
    {
        // Traiter le formulaire de paramètres si soumis
        $this->processSettingsForm();

        // Récupérer tous les outils disponibles
        $allTools = ToolManager::getAllAvailableTools();

        // Filtrer les outils filtrés pour l'affichage
        $filterTools = $this->filterSettingsTools($allTools);

        // Récupérer les paramètres actuels
        $settings = get_option('wp_debug_toolkit_settings', [
            'acces_level' => 'manage_options',
            'enable_logging' => false,
            'developer_mode' => false
        ]);

        // Récupérer les outils actifs pour l'utilisateur
        $activeTools = get_user_meta(get_current_user_id(), 'wp_debug_toolkit_active_tools', true);
        if (!is_array($activeTools)) {
            $activeTools = [];
            foreach ($allTools as $toolId => $tool) {
                $activeTools[$toolId] = $tool['active'];
            }
        }

        // Inclure la vue des paramètres
        require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . '/src/Admin/View/settings.php';
    }

    private function processSettingsForm(): void
    {
        // Vérifie si le formulaire a été soumis
        if (isset($_POST['wp_debug_toolkit_settings_submit'])) {
            // Vérifie le nonce
            if (!isset($_POST['wp_debug_toolkit_settings_nonce']) ||
                !wp_verify_nonce($_POST['wp_debug_toolkit_settings_nonce'], 'wp_debug_toolkit_settings')) {
                $this->showNotice(__('Erreur de sécurité. Veuillez réessayer.', 'wp-debug-toolkit'), 'error');
                return;
            }

            // Récupère et nettoie les données des paramètres généraux
            $settings = [
                'acces_level' => sanitize_text_field($_POST['acces_level'] ?? 'manage_options'),
                'enable_logging' => isset($_POST['enable_logging']),
                'developer_mode' => isset($_POST['developer_mode'])
            ];

            // Enregistre les paramètres généraux
            update_option('wp_debug_toolkit_settings', $settings);

            // Traitement des outils actifs
            $allTools = ToolManager::getAllAvailableTools();
            $activeTools = [];

            // Initialiser tous les outils comme inactifs
            foreach ($allTools as $toolId => $tool) {
                $activeTools[$toolId] = false;
            }

            // Activer les outils sélectionnés
            if (isset($_POST['active_tools']) && is_array($_POST['active_tools'])) {
                foreach ($_POST['active_tools'] as $toolId) {
                    if (isset($allTools[$toolId])) {
                        $activeTools[$toolId] = true;
                    }
                }
            }

            // Sauvegarder dans les métadonnées de l'utilisateur
            update_user_meta(get_current_user_id(), 'wp_debug_toolkit_active_tools', $activeTools);

            // Mettre également à jour l'option globale pour les nouveaux utilisateurs
            $toolSettings = get_option('wp_debug_toolkit_tools', []);
            $toolSettings = array_merge($toolSettings, $activeTools);
            update_option('wp_debug_toolkit_tools', $toolSettings);

            // Affiche un message de succès
            $this->showNotice(__('Paramètres enregistrés avec succès.', 'wp-debug-toolkit'), 'success');
        }
    }

    public static function filterSettingsTools(array $tools): array
    {
        return $tools;
    }

    public function enqueueAssets(string $hook): void
    {
        // Enqueue CSS
        wp_enqueue_style(
            'wp-debug-toolkit-settings-css',
            WP_DEBUG_TOOLKIT_PLUGIN_URL . 'assets/css/settings.css',
            [],
            WP_DEBUG_TOOLKIT_VERSION . '-' . time()
        );
    }
}
