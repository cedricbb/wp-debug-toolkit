<?php

namespace WPDebugToolkit\Admin\Page;

class Settings extends AbstractPage
{
    public function __construct()
    {
        parent::__construct('settings', __('Paramètres', 'wp-debug-toolkit'), 'dashicons-admin-settings');
    }

    public function render(): void
    {
        // Traiter le formulaire de paramètres si soumis
        $this->processSettingsForm();

        // Récupérer les paramètres actuels
        $settings = get_option('wp_debug_toolkit_settings', [
            'acces_level' => 'manage_options',
            'enable_logging' => false,
            'developer_mode' => false
        ]);

        // Inclure la vue des paramètres
        require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . '/src/Admin/View/settings.php';
    }

    private function processSettingsForm(): void
    {
        // Vérifie si le formulaire a été soumis
        if (isset($_POST['wp_debug_toolkit_settings_submit'])) {
            // Vérifie le nonce
            if (!$this->verifyNonce($_POST['wp_debug_toolkit_settings_nonce'] ?? '')) {
                $this->showNotice(__('Erreur de sécurité. Veuillez réessayer.', 'wp-debug-toolkit'), 'error');
                return;
            }

            // Récupère et nettoie les données
            $settings = [
                'acces_level' => sanitize_text_field($_POST['acces_level'] ?? 'manage_options'),
                'enable_logging' => isset($_POST['enable_logging']),
                'developer_mode' => isset($_POST['developer_mode'])
            ];

            // Enregistre les paramètres
            update_option('wp_debug_toolkit_settings', $settings);

            // Affiche un message de succès
            $this->showNotice(__('Paramètres enregistrés avec succès.', 'wp-debug-toolkit'), 'success');
        }
    }
}
