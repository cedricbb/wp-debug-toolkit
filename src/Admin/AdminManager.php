<?php
/**
 * Classe pour gérer l'interface d'administration du plugin
 * @package WP_Debug_Toolkit
 */

namespace WPDebugToolkit\Admin;

use WPDebugToolkit\Admin\Page\Dashboard;
use WPDebugToolkit\Admin\Page\Settings;
use WPDebugToolkit\Admin\Page\About;

/**
 * Classe pour gérer l'interface d'administration
 */
class AdminManager
{
    private array $tabs = [];
    private array $pages = [];

    public function init(): void
    {
        // Initialiser les pages d'administration par défaut
        $this->initDefaultPages();

        // Ajouter les hooks d'administration
        add_action('admin_menu', [$this, 'addAdminMenu']);
    }

    private function initDefaultPages(): void
    {
        // Créer les instances des pages d'administration par défaut
        $this->pages['dashboard'] = new Dashboard();
        $this->pages['settings'] = new Settings();
        $this->pages['about'] = new About();

        // Permettre l'ajout de pages supplémentaires via un filtre
        $this->pages = apply_filters('wp_debug_toolkit_pages', $this->pages);
    }

    public function addAdminMenu(): void
    {
        // Ajouter le menu principal
        add_menu_page(
            __('WP Debug Toolkit', 'wp-debug-toolkit'),
            __('Debug Toolkit', 'wp-debug-toolkit'),
            'manage_options',
            'wp-debug-toolkit',
            [$this->pages['dashboard'], 'render'],
            'dashicons-admin-tools',
            100
        );

        // Ajouter un sous-menu pour le tableau de bord (même que le menu principal)
        add_submenu_page(
            'wp-debug-toolkit',
            __('Tableau de bord', 'wp-debug-toolkit'),
            __('Tableau de bord', 'wp-debug-toolkit'),
            'manage_options',
            'wp-debug-toolkit',
            [$this->pages['dashboard'], 'render']
        );

        // Ajouter un sous-menu pour chaque page
        foreach ($this->pages as $page_id => $page) {
            // Sauter le tableau de bord car nous l'avons déjà ajouté
            if ($page_id === 'dashboard') {
                continue;
            }

            add_submenu_page(
                'wp-debug-toolkit',
                $page->getTitle(),
                $page->getTitle(),
                'manage_options',
                'wp-debug-toolkit-' . $page_id,
                [$page, 'render']
            );
        }

        // Ajouter les sous-menus pour les outils (à travers un hook pour permettre aux outils de s'enregistrer)
        do_action('wp_debug_toolkit_register_tool_pages', 'wp-debug-toolkit');
    }

    public function addPage(string $id, $pageInstance): void
    {
        $this->pages[$id] = $pageInstance;
    }

    public function getPages(): array
    {
        return $this->pages;
    }
}