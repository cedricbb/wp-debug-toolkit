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

        // Initialiser les onglets
        $this->tabs = [
            'dashboard' => [
                'title' => __('Tableau de bord', 'wp-debug-toolkit'),
                'icon' => 'dashicons-dashboard',
            ],
            'settings' => [
                'title' => __('Paramètres', 'wp-debug-toolkit'),
                'icon' => 'dashicons-admin-settings',
            ],
            'about' => [
                'title' => __('À propos', 'wp-debug-toolkit'),
                'icon' => 'dashicons-info',
            ]
        ];

        // Permettre l'ajout d'onglets supplémentaires
        $this->tabs = apply_filters('wp_debug_toolkit_tabs', $this->tabs);
    }

    public function addAdminMenu(): void
    {
        add_menu_page(
            __('WP Debug Toolkit', 'wp-debug-toolkit'),
            __('Debug Toolkit', 'wp-debug-toolkit'),
            'manage_options',
            'wp-debug-toolkit',
            [$this, 'displayAdminPage'],
            'dashicons-admin-tools',
            100
        );

        // Ajouter un sous-menu pour chaque outil
        foreach ($this->tabs as $tab_id => $tab) {
            add_submenu_page(
                'wp-debug-toolkit',
                $tab['title'],
                $tab['title'],
                'manage_options',
                'wp-debug-toolkit-' . $tab_id,
                [$this, 'displayToolPage']
            );
        }
    }

    public function displayAdminPage(): void
    {
        // Déterminer l'onglet actif
        $currentTab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

        // Vérifier si l'onglet existe
        if (!isset($this->tabs[$currentTab])) {
            $currentTab = 'dashboard';
        }

        // Afficher l'en-tête
        $this->renderHeader();

        // Afficher la navigation
        $this->renderNavigation($currentTab);

        // Afficher le contenu de l'onglet
        if (isset($this->tabs[$currentTab])) {
            $this->pages[$currentTab]->render();
        } else {
            do_action('wp_debug_toolkit_tab_content_', $currentTab);
        }

        // Afficher le pied de page
        $this->renderFooter();
    }

    public function displayToolPage(): void
    {
        // Obtenir l'ID de l'outil à partir de la apge actuelle
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        $toolID = str_replace('wp-debug-toolkit-', '', $page);

        // Rediriger vers l'onglet correspondant
        wp_redirect(admin_url('admin.php?page=wp-debug-toolkit&tab=' . $toolID));
        exit;
    }

    private function renderHeader(): void
    {
        require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'src/Admin/View/header.php';
    }

    private function renderNavigation(string $currentTab): void
    {
        $tabs = $this->tabs;
        require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'src/Admin/View/navigation.php';
    }

    private function renderFooter(): void
    {
        require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'src/Admin/View/footer.php';
    }

    public function addPage(string $id, $pageInstance): void
    {
        $this->pages[$id] = $pageInstance;
    }

    public function getPages(): array
    {
        return $this->pages;
    }

    public function getTabs(): array
    {
        return $this->tabs;
    }

    public function addTab(string $id, array $tabData): void
    {
        $this->tabs[$id] = $tabData;
    }
}