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
        ?>
        <style>
            .switch {
                position: relative;
                display: inline-block;
                width: 60px;
                height: 34px;
            }

            .switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 34px;
            }

            .slider:before {
                position: absolute;
                content: "";
                height: 26px;
                width: 26px;
                left: 4px;
                bottom: 4px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }

            input:checked + .slider {
                background-color: var(--wp-debug-primary);
            }

            input:focus + .slider {
                box-shadow: 0 0 1px var(--wp-debug-primary);
            }

            input:checked + .slider:before {
                transform: translateX(26px);
            }

            .status {
                font-weight: 500;
                color: #555;
                min-width: 60px;
            }

            .switch-label {
                font-size: 16px;
                color: #333;
                user-select: none;
            }
        </style>
        <div class="switch-container">
            <div class="switch-row">
                <label class="switch">
                    <input type="checkbox" id="toggle1">
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        <?php
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
