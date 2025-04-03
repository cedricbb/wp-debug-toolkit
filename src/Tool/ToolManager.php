<?php

namespace WPDebugToolkit\Tool;

use WPDebugToolkit\Admin\Page\Settings;
use WPDebugToolkit\Core\Plugin;
use WPDebugToolkit\Tool\ToolInterface;

/**
 * Classe pour gérer les outils et leur personnalisation
 */
class ToolManager
{
    /**
     * Le hook de la page du tableau de bord
     */
    private $pageHook;

    /**
     * Stockage des instances d'outils
     */
    private array $tools = [];

    /**
     * Configuration d'activation des outils
     */
    private array $activeTools = [];

    /**
     * Initialise le gestionnaire d'outils
     */
    public function init(): void
    {
        // Charger les paramètres des outils
        $this->loadToolSettings();

        // Charger les outils actifs
        $this->loadActiveTools();

        // Ajouter l'action pour enregistrer les pages d'outils dans l'admin
        add_action('wp_debug_toolkit_register_tool_pages', [$this, 'registerToolPages']);

        // Ajouter les scripts et styles
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);

        // Ajouter les endpoints AJAX
        add_action('wp_ajax_wp_debug_toolkit_save_tools_order', [$this, 'saveToolsOrder']);
        add_action('wp_ajax_wp_debug_toolkit_save_tool_preference', [$this, 'saveToolPreference']);
        add_action('wp_ajax_wp_debug_toolkit_get_available_tools', [$this, 'getAvailableTools']);

        // Filtrer les outils affichés sur le tableau de bord selon préférences utilisateur
        add_filter('wp_debug_toolkit_tools', [$this, 'filterDashboardTools'], 10, 1);

        // Ajouter des attributs data aux cartes d'outils
        add_action('wp_debug_toolkit_tool_card_attributes', [$this, 'addToolCardAttributes'], 10, 1);
    }

    /**
     * Charge les paramètres des outils depuis les options WordPress
     */
    private function loadToolSettings(): void
    {
        // Récupérer les paramètres des outils depuis les options
        $toolSettings = get_option('wp_debug_toolkit_tools', []);

        // Par défaut, activer tous les outils si aucun paramètre n'est défini
        if (empty($toolSettings)) {
            $this->activeTools = [
                'elementor-block-analyzer' => true,
                'elementor-form-analyzer' => true,
                'api-monitor' => true,
                'media-cleaner' => true,
                'hook-inspector' => true,
                'query-profiler' => true,
                'cache-inspector' => true,
                'cron-monitor' => true
            ];
        } else {
            $this->activeTools = $toolSettings;
        }
    }

    /**
     * Charge les outils actifs
     */
    private function loadActiveTools(): void
    {
        // Charger les outils actifs
        foreach ($this->activeTools as $toolId => $isActive) {
            if ($isActive) {
                $this->loadTool($toolId);
            }
        }
    }

    /**
     * Charge un outil spécifique
     */
    private function loadTool(string $toolId): void
    {
        // Convertir l'ID de l'outil en nom de classe
        $className = $this->getToolClassName($toolId);
        $fullClassName = 'WPDebugToolkit\\Tool\\' . $className . '\\' . $className;

        // Vérifier si la classe existe
        if (class_exists($fullClassName)) {
            // Créer l'instance de l'outil
            $tool = new $fullClassName();

            // Vérifier si l'outil implémente l'interface requise
            if ($tool instanceof ToolInterface) {
                // Initialiser l'outil
                $tool->init();

                // Stocker l'instance de l'outil
                $this->tools[$toolId] = $tool;
            }
        }
    }

    /**
     * Convertit un ID d'outil en nom de classe
     */
    private function getToolClassName(string $toolId): string
    {
        // Convertir elementor-block-analyzer en ElementorBlockAnalyzer
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $toolId)));
    }

    /**
     * Affiche les notifications administratives
     */
    public function displayAdminNotices(): void
    {
        // Vérifier si nous sommes sur la bonne page
        $screen = get_current_screen();
        if ($screen->base !== 'toplevel_page_wp-debug-toolkit') {
            return;
        }

        // Vérifier si un message de succès doit être affiché
        if (get_transient('wp_debug_toolkit_options_saved')) {
            // Supprimer le drapeau
            delete_transient('wp_debug_toolkit_options_saved');

            // Afficher le message
            echo '<div class="notice notice-success is-dismissible"><p>' .
                __('Options enregistrées avec succès.', 'wp-debug-toolkit') .
                '</p></div>';
        }
    }

    /**
     * Enqueue les assets nécessaires
     */
    public function enqueueAssets($hook): void
    {
        // Vérifier si nous sommes sur la page du tableau de bord du plugin
        if (strpos($hook, 'wp-debug-toolkit') === false) {
            return;
        }

        // Enqueue jQuery UI et ses dépendances
        wp_enqueue_script('jquery-ui-sortable');

        // Localiser le script avec les variables et les traductions
        wp_localize_script('wp-debug-toolkit-admin-js', 'wp_debug_toolkit_customizer', [
            'nonce' => wp_create_nonce('wp_debug_toolkit_customizer'),
            'saved_text' => __('Modifications enregistrées', 'wp-debug-toolkit'),
            'user_preferences' => self::getUserToolPreferences()
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

        // Forcer le format array
        if (!is_array($activeTools)) {
            $activeTools = [];
        }

        // Vérifier que les clés correspondent aux IDs des outils
        $hasValidKeys = false;
        foreach (array_keys($activeTools) as $key) {
            if (isset($tools[$key])) {
                $hasValidKeys = true;
                break;
            }
        }

        // Si aucune clé valide, réinitialiser les préférences
        if (!$hasValidKeys) {
            $activeTools = [];
            foreach ($tools as $toolId => $tool) {
                $activeTools[$toolId] = true;
            }
            update_user_meta(get_current_user_id(), 'wp_debug_toolkit_active_tools', $activeTools);
        }

        // Filtrer les outils en ne gardant que ceux qui sont actifs
        $filteredTools = [];
        foreach ($tools as $toolId => $tool) {
            if (!isset($activeTools[$toolId]) || $activeTools[$toolId]) {
                $filteredTools[$toolId] = $tool;
            }
        }

        // Si tous les outils sont désactivés, retourner quand même les outils originaux
        if (empty($filteredTools)) {
            return $tools;
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
     * Enregistre les pages d'administration pour chaque outil actif
     */
    public function registerToolPages(string $parentSlug): void
    {
        // Ajouter un sous-menu pour chaque outil actif
        foreach ($this->tools as $toolId => $tool) {
            add_submenu_page(
                $parentSlug,
                $tool->getTitle(),
                $tool->getTitle(),
                'manage_options',
                'wp-debug-toolkit-' . $toolId,
                [$this, 'renderToolPage']
            );
        }
    }

    /**
     * Affiche la page d'un outil
     */
    public function renderToolPage(): void
    {
        // Obtenir l'ID de l'outil à partir de la page actuelle
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        $toolId = str_replace('wp-debug-toolkit-', '', $page);

        // Vérifier si l'outil existe
        if (isset($this->tools[$toolId])) {
            echo '<div class="wrap">';

            // Afficher l'en-tête si nécessaire
            require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'src/Admin/View/header.php';

            echo '<h2>' . esc_html($this->tools[$toolId]->getTitle()) . '</h2>';

            // Afficher le contenu de l'outil
            $this->tools[$toolId]->renderContent();

            // Afficher le pied de page si nécessaire
            require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'src/Admin/View/footer.php';

            echo '</div>';
        } else {
            wp_die(__('Outil non trouvé.', 'wp-debug-toolkit'));
        }
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

        wp_send_json_success();
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

        // Récupérer tous les outils disponibles
        $tools = self::getAllAvailableTools();

        // Initialiser le tableau des outils actifs à tous désactivés
        $sanitizedActiveTools = [];
        foreach ($tools as $toolId => $tool) {
            $sanitizedActiveTools[$toolId] = isset($activeTools[$toolId]) &&
                ($activeTools[$toolId] === true ||
                    $activeTools[$toolId] === 'true' ||
                    $activeTools[$toolId] === '1' ||
                    $activeTools[$toolId] === 1);
        }

        // Sauvegarder dans les métadonnées de l'utilisateur
        update_user_meta(get_current_user_id(), 'wp_debug_toolkit_active_tools', $sanitizedActiveTools);

        // Mettre également à jour l'option globale pour les nouveaux utilisateurs
        $toolSettings = get_option('wp_debug_toolkit_tools', []);
        $toolSettings = array_merge($toolSettings, $sanitizedActiveTools);
        update_option('wp_debug_toolkit_tools', $toolSettings);

        wp_send_json_success();
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

        wp_send_json_success();
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
        $tools = self::getAllAvailableTools();

        // Récupérer les outils actifs pour l'utilisateur
        $activeTools = get_user_meta(get_current_user_id(), 'wp_debug_toolkit_active_tools', true);

        // Mettre à jour l'état actif des outils
        if (is_array($activeTools)) {
            foreach ($tools as $toolId => &$tool) {
                $tool['active'] = isset($activeTools[$toolId]) && $activeTools[$toolId];
            }
        }

        wp_send_json_success($tools);
    }

    /**
     * Récupère tous les outils disponibles (y compris ceux non chargés)
     */
    public static function getAllAvailableTools(): array
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
    public static function getUserToolPreferences(): array
    {
        $preferences = get_user_meta(get_current_user_id(), 'wp_debug_toolkit_tool_preferences', true);

        if (!is_array($preferences)) {
            return [];
        }

        return $preferences;
    }

    /**
     * Vérifie si un outil est actif
     */
    public function isToolActive(string $toolId): bool
    {
        return isset($this->activeTools[$toolId]) && $this->activeTools[$toolId];
    }

    /**
     * Définit l'état d'activation d'un outil
     */
    public function setToolActive(string $toolId, bool $active = true): void
    {
        $this->activeTools[$toolId] = $active;
        update_option('wp_debug_toolkit_tools', $this->activeTools);
    }

    /**
     * Récupère toutes les instances d'outils chargées
     */
    public function getTools(): array
    {
        return $this->tools;
    }
}
