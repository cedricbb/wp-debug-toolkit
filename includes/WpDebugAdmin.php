<?php
/**
 * Classe pour gérer l'interface d'administration du plugin
 * @package WP_Debug_Toolkit
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour gérer l'interface d'administration
 */
class WPDebugAdmin {

    private static $instance = null;
    private array $tabs = [];

    public function __construct() {
        // Initialiser les onglets par défaut
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

        // Sous-menus pour chaque outil
        $this->addToolSubmenu();
    }

    private function addToolSubmenu(): void
    {
        // Sous-menu pour les paramètres
        add_submenu_page(
            'wp-debug-toolkit',
            __('Paramètres', 'wp-debug-toolkit'),
            __('Paramètres', 'wp-debug-toolkit'),
            'manage_options',
            'wp-debug-toolkit-settings',
            [$this, 'displaySettingsPage']
        );

        // Filtrer les onglets pour permettre aux outils d'ajouter leurs propres onglets
        $this->tabs = apply_filters('wp_debug_toolkit_tabs', $this->tabs);

        // Ajouter un sous-menu pour chaque outil
        foreach ($this->tabs as $tab_id => $tab) {
            // Sauter les onglets par défaut qui sont déjà ajoutés
            if (in_array($tab_id, ['dashboard', 'settings', 'about'])) {
                continue;
            }

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

        // Inclure le template de l'en-tête
        include WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'admin/partials/header.php';
        // Inclure le template de navigation
        include WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'admin/partials/navigation.php';
        // Déclencher une action pour permettre aux outils d'afficher leur contenu
        do_action('wp_debug_toolkit_tab_content', $currentTab);
        // Si aucun outil n'a affiché de contenu, afficher le template par défaut
        if (!did_action('wp_debug_toolkit_tab_content_' . $currentTab)) {
            // Inclure le template correspondant à l'onglet
            $templatePath = WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'admin/views/' . $currentTab . '.php';

            if (file_exists($templatePath)) {
                include $templatePath;
            } else {
                echo '<div class="wrap"><p>' . __('Template non trouvé.', 'wp-debug-toolkit') . '</p></div>';
            }
        }

        // Inclure le template du pied de page
        include WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'admin/partials/footer.php';
    }

    public function displaySettingsPage(): void
    {
        // Rediriger vers l'onglet settings
        wp_redirect(admin_url('admin.php?page=wp-debug-toolkit&tab=settings'));
        exit;
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

    public function enqueueAdminScript($hook): void
    {
        // Vérifier si nous sommes sur une page du plugin
        if (str_starts_with($hook, 'wp_debug_toolkit_')) {
            return;
        }

        // Enregistrer et charger le CSS principal
        wp_enqueue_style(
            'wp-debug-toolkit-admin',
            WP_DEBUG_TOOLKIT_PLUGIN_URL . 'assets/css/admin.css',
            [],
            WP_DEBUG_TOOLKIT_VERSION
        );

        // Enregistrer et charger le JS principal
        wp_enqueue_script(
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

    public static function getInstance(): ?WPDebugAdmin
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}