<?php

namespace WPDebugToolkit\Admin;

/**
 * Classe pour gérer la personnalisation du tableau de bord
 */
class DashboardCustomizer
{
    /**
     * Le hook de la page du tableau de bord
     */
    private $pageHook;

    /**
     * Initialisation
     */
    public function init(): void
    {
        // Ajouter les scripts et styles
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);

        // Ajouter les endpoints AJAX
        add_action('wp_ajax_wp_debug_toolkit_save_tools_order', [$this, 'saveToolsOrder']);
        add_action('wp_ajax_wp_debug_toolkit_save_active_tools', [$this, 'saveActiveTools']);
        add_action('wp_ajax_wp_debug_toolkit_save_tool_preference', [$this, 'saveToolPreference']);
        add_action('wp_ajax_wp_debug_toolkit_get_available_tools', [$this, 'getAvailableTools']);

        // Filtrer les outils affichés sur le tableau de bord
        add_filter('wp_debug_toolkit_tools', [$this, 'filterDashboardTools'], 10, 1);

        // Ajouter des attributs data aux cartes d'outils
        add_action('wp_debug_toolkit_tool_card_attributes', [$this, 'addToolCardAttributes'], 10, 1);

        // Ajouter du contenu aux options d'écran
        add_action('admin_init', [$this, 'setupScreenOptions']);

        // Options d'écran pour chaque outil
        add_filter('screen_settings', [$this, 'addScreenOptions'], 10, 2);
    }

    /**
     * Configuration des options d'écran
     */
    public function setupScreenOptions(): void
    {
        // Nous devons connaître le hook de la page pour configurer les options d'écran
        add_action('toplevel_page_wp-debug-toolkit', [$this, 'setPageHook']);
        add_action('load-toplevel_page_wp-debug-toolkit', [$this, 'addScreenOptionsTab']);
    }

    /**
     * Enregistre le hook de la page
     */
    public function setPageHook($hook): void
    {
        $this->pageHook = $hook;
    }

    /**
     * Ajoute l'onglet des options d'écran
     */
    public function addScreenOptionsTab(): void
    {
        // Activer les options d'écran pour cette page
        add_filter('screen_options_show_screen', '__return_true');

        // Obtenir l'objet écran actuel
        $screen = get_current_screen();

        // Ajouter une aide contextuelle (optionnel mais utile)
        $screen->add_help_tab([
            'id'      => 'wp-debug-toolkit-help',
            'title'   => __('Aide', 'wp-debug-toolkit'),
            'content' => '<p>' . __('Vous pouvez réorganiser les outils par glisser-déposer ou choisir quels outils afficher dans les options d\'écran.', 'wp-debug-toolkit') . '</p>',
        ]);
    }

    /**
     * Ajoute des options d'écran personnalisées
     */
    public function addScreenOptions($screen_settings, $screen): string
    {
        // Vérifier si nous sommes sur la bonne page
        if ($screen->base !== 'toplevel_page_wp-debug-toolkit') {
            return $screen_settings;
        }

        // Récupérer les outils disponibles
        $tools = $this->getAllTools();

        // Récupérer les outils actifs pour l'utilisateur
        $activeTools = get_user_meta(get_current_user_id(), 'wp_debug_toolkit_active_tools', true);

        if (!is_array($activeTools)) {
            $activeTools = [];
            foreach ($tools as $toolId => $tool) {
                $activeTools[$toolId] = $tool['active'];
            }
        }

        // Créer les options d'écran pour les outils
        $output = '<fieldset class="metabox-prefs">';
        $output .= '<legend>' . __('Outils', 'wp-debug-toolkit') . '</legend>';
        $output .= '<div id="wp-debug-toolkit-available-tools">';

        foreach ($tools as $toolId => $tool) {
            $isActive = isset($activeTools[$toolId]) ? $activeTools[$toolId] : true;
            $output .= '<label>';
            $output .= '<input type="checkbox" class="hide-postbox-tog" name="wp-debug-toolkit-tool-' . esc_attr($toolId) . '-hide"';
            $output .= ' value="' . esc_attr($toolId) . '"' . checked($isActive, true, false) . '>';
            $output .= ' ' . esc_html($tool['title']);
            $output .= '</label>';
        }

        $output .= '</div>';
        $output .= '<button type="button" class="button button-primary" id="wp-debug-toolkit-save-screen-options">';
        $output .= __('Appliquer', 'wp-debug-toolkit');
        $output .= '</button>';
        $output .= '</fieldset>';

        return $screen_settings . $output;
    }

    /**
     * Enqueue les assets nécessaires
     */
    public function enqueueAssets($hook): void
    {
        // Vérifier si nous sommes sur la page du plugin
        if (!str_contains($hook, 'wp-debug-toolkit')) {
            return;
        }

        // Enqueue jQuery UI et ses dépendances
        wp_enqueue_script('jquery-ui-sortable');

        // Enqueue le script de personnalisation
        wp_enqueue_script(
            'wp-debug-toolkit-customizer',
            WP_DEBUG_TOOLKIT_PLUGIN_URL . 'assets/js/dashboard-customizer.js',
            ['jquery', 'jquery-ui-sortable'],
            WP_DEBUG_TOOLKIT_VERSION,
            true
        );

        // Enqueue les styles
        wp_enqueue_style(
            'wp-debug-toolkit-customizer',
            WP_DEBUG_TOOLKIT_PLUGIN_URL . 'assets/css/dashboard-customizer.css',
            [],
            WP_DEBUG_TOOLKIT_VERSION
        );

        // Localiser le script avec les variables et les traductions
        wp_localize_script('wp-debug-toolkit-customizer', 'wp_debug_toolkit_customizer', [
            'nonce' => wp_create_nonce('wp_debug_toolkit_customizer'),
            'saved_text' => __('Modifications enregistrées', 'wp-debug-toolkit'),
            'user_preferences' => $this->getUserToolPreferences()
        ]);
    }

    /**
     * Ajoute des attributs data aux cartes d'outils pour le JavaScript
     *
     * @param string|array $toolId L'ID de l'outil
     */
    public function addToolCardAttributes($toolId): void
    {
        // S'assurer que nous avons une chaîne
        if (is_array($toolId)) {
            $toolId = $toolId[0] ?? '';
        }

        echo ' data-tool-id="' . esc_attr($toolId) . '"';
    }

    /**
     * Filtre les outils affichés sur le tableau de bord en fonction des préférences
     */
    public function filterDashboardTools(array $tools): array
    {
        // Récupérer les outils actifs pour l'utilisateur
        $activeTools = get_user_meta(get_current_user_id(), 'wp_debug_toolkit_active_tools', true);

        // Si aucune préférence n'est enregistrée, utiliser tous les outils
        if (!is_array($activeTools)) {
            return $tools;
        }

        // Filtrer les outils en ne gardant que ceux qui sont actifs
        $filteredTools = [];
        foreach ($tools as $toolId => $tool) {
            if (isset($activeTools[$toolId]) && $activeTools[$toolId]) {
                $filteredTools[$toolId] = $tool;
            }
        }

        // Réordonner les outils selon l'ordre sauvegardé
        $toolsOrder = get_user_meta(get_current_user_id(), 'wp_debug_toolkit_tools_order', true);

        if (is_array($toolsOrder) && !empty($toolsOrder)) {
            $orderedTools = [];

            // D'abord ajouter les outils dans l'ordre sauvegardé
            foreach ($toolsOrder as $toolId) {
                if (isset($filteredTools[$toolId])) {
                    $orderedTools[$toolId] = $filteredTools[$toolId];
                    unset($filteredTools[$toolId]);
                }
            }

            // Ensuite ajouter les nouveaux outils qui n'ont pas encore d'ordre défini
            return array_merge($orderedTools, $filteredTools);
        }

        return $filteredTools;
    }

    /**
     * Sauvegarde l'ordre des outils via AJAX
     */
    public function saveToolsOrder(): void
    {
        // Vérifier le nonce
        if (!check_ajax_referer('wp_debug_toolkit_customizer', 'nonce', false)) {
            wp_send_json_error(['message' => __('Erreur de sécurité', 'wp-debug-toolkit')]);
        }

        // Récupérer et valider l'ordre des outils
        $toolsOrder = isset($_POST['order']) ? (array) $_POST['order'] : [];
        $toolsOrder = array_map('sanitize_key', $toolsOrder);

        // Sauvegarder l'ordre dans les métadonnées de l'utilisateur
        update_user_meta(get_current_user_id(), 'wp_debug_toolkit_tools_order', $toolsOrder);

        wp_send_json_success(['message' => __('Ordre des outils sauvegardé', 'wp-debug-toolkit')]);
    }

    /**
     * Sauvegarde les outils actifs via AJAX
     */
    public function saveActiveTools(): void
    {
        // Vérifier le nonce
        if (!check_ajax_referer('wp_debug_toolkit_customizer', 'nonce', false)) {
            wp_send_json_error(['message' => __('Erreur de sécurité', 'wp-debug-toolkit')]);
        }

        // Récupérer et valider les outils actifs
        $activeTools = isset($_POST['active_tools']) ? (array) $_POST['active_tools'] : [];

        // Sanitize
        $sanitizedActiveTools = [];
        foreach ($activeTools as $toolId => $isActive) {
            $sanitizedActiveTools[sanitize_key($toolId)] = (bool) $isActive;
        }

        // Sauvegarder dans les métadonnées de l'utilisateur
        update_user_meta(get_current_user_id(), 'wp_debug_toolkit_active_tools', $sanitizedActiveTools);

        // Mettre également à jour l'option globale pour les nouveaux utilisateurs
        $toolSettings = get_option('wp_debug_toolkit_tools', []);
        $toolSettings = array_merge($toolSettings, $sanitizedActiveTools);
        update_option('wp_debug_toolkit_tools', $toolSettings);

        wp_send_json_success(['message' => __('Outils actifs sauvegardés', 'wp-debug-toolkit')]);
    }

    /**
     * Sauvegarde une préférence utilisateur pour un outil via AJAX
     */
    public function saveToolPreference(): void
    {
        // Vérifier le nonce
        if (!check_ajax_referer('wp_debug_toolkit_customizer', 'nonce', false)) {
            wp_send_json_error(['message' => __('Erreur de sécurité', 'wp-debug-toolkit')]);
        }

        // Récupérer et valider les données
        $toolId = isset($_POST['tool_id']) ? sanitize_key($_POST['tool_id']) : '';
        $prefKey = isset($_POST['pref_key']) ? sanitize_key($_POST['pref_key']) : '';
        $prefValue = $_POST['pref_value'] ?? '';

        // Valider le bool si c'est un bool
        if ($prefValue === 'true') {
            $prefValue = true;
        } elseif ($prefValue === 'false') {
            $prefValue = false;
        }

        // Récupérer les préférences actuelles
        $preferences = get_user_meta(get_current_user_id(), 'wp_debug_toolkit_tool_preferences', true);

        if (!is_array($preferences)) {
            $preferences = [];
        }

        if (!isset($preferences[$toolId])) {
            $preferences[$toolId] = [];
        }

        // Mettre à jour la préférence
        $preferences[$toolId][$prefKey] = $prefValue;

        // Sauvegarder
        update_user_meta(get_current_user_id(), 'wp_debug_toolkit_tool_preferences', $preferences);

        wp_send_json_success(['message' => __('Préférences des outils sauvegardées', 'wp-debug-toolkit')]);
    }

    /**
     * Récupère tous les outils disponibles via AJAX
     */
    public function getAvailableTools(): void
    {
        // Vérifier le nonce
        if (!check_ajax_referer('wp_debug_toolkit_customizer', 'nonce', false)) {
            wp_send_json_error(['message' => __('Erreur de sécurité', 'wp-debug-toolkit')]);
        }

        // Récupérer tous les outils (avant le filtrage)
        $tools = $this->getAllTools();

        // Récupérer les outils actifs pour l'utilisateur
        $activeTools = get_user_meta(get_current_user_id(), 'wp_debug_toolkit_active_tools', true);

        // Mettre à jour l'état actif des outils
        if (is_array($activeTools)) {
            foreach ($tools as $toolId => &$tool) {
                $tool['active'] = isset($activeTools[$toolId]) && (bool) $activeTools[$toolId];
            }
        }

        wp_send_json_success($tools);
    }

    /**
     * Récupère tous les outils disponibles
     */
    private function getAllTools(): array
    {
        return apply_filters('wp_debug_toolkit_all_tools', [
            'elementor-block-analyzer' => [
                'title' => __('Analyseur de blocs Elementor', 'wp-debug-toolkit'),
                'description' => __('Analyse l\'utilisation des widgets Elementor sur le site', 'wp-debug-toolkit'),
                'icon' => 'dashicons-welcome-widgets-menus',
                'active' => true
            ],
            'elementor-form-analyzer' => [
                'title' => __('Analyseur de formulaires Elementor', 'wp-debug-toolkit'),
                'description' => __('Analyse la configuration des formulaires Elementor', 'wp-debug-toolkit'),
                'icon' => 'dashicons-forms',
                'active' => true
            ],
            'api-monitor' => [
                'title' => __('Moniteur d\'API', 'wp-debug-toolkit'),
                'description' => __('Surveille les appels API REST entrants et sortants', 'wp-debug-toolkit'),
                'icon' => 'dashicons-rest-api',
                'active' => true
            ],
            'media-cleaner' => [
                'title' => __('Nettoyeur de médias', 'wp-debug-toolkit'),
                'description' => __('Identifie et nettoie les médias inutilisés', 'wp-debug-toolkit'),
                'icon' => 'dashicons-images-alt2',
                'active' => true
            ],
            'hook-inspector' => [
                'title' => __('Inspecteur de hooks', 'wp-debug-toolkit'),
                'description' => __('Visualise les actions et filtres WordPress', 'wp-debug-toolkit'),
                'icon' => 'dashicons-admin-links',
                'active' => true
            ],
            'query-profiler' => [
                'title' => __('Profileur de requêtes SQL', 'wp-debug-toolkit'),
                'description' => __('Analyse les performances des requêtes SQL', 'wp-debug-toolkit'),
                'icon' => 'dashicons-database',
                'active' => true
            ],
            'cache-inspector' => [
                'title' => __('Inspecteur de cache', 'wp-debug-toolkit'),
                'description' => __('Examine les objets en cache', 'wp-debug-toolkit'),
                'icon' => 'dashicons-performance',
                'active' => true
            ],
            'cron-monitor' => [
                'title' => __('Moniteur de cron', 'wp-debug-toolkit'),
                'description' => __('Surveille les tâches planifiées WordPress', 'wp-debug-toolkit'),
                'icon' => 'dashicons-clock',
                'active' => true
            ],
        ]);
    }

    /**
     * Récupère les préférences utilisateur pour tous les outils
     */
    private function getUserToolPreferences(): array
    {
        $preferences = get_user_meta(get_current_user_id(), 'wp_debug_toolkit_tool_preferences', true);

        if (!is_array($preferences)) {
            return [];
        }

        return $preferences;
    }
}