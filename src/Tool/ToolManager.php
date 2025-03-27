<?php

namespace WPDebugToolkit\Tool;

use WPDebugToolkit\Core\Plugin;
use WPDebugToolkit\Tool\ToolInterface;

class ToolManager
{
    private array $tools = [];
    private array $activeTools = [];

    public function init(): void
    {
        // Charger les paramètres des outils
        $this->loadToolSettings();

        // Charger les outils actifs
        $this->loadActiveTools();

        // Enregistrer le hook pour les onglets et le contenu des onglets
        add_action('wp_debug_toolkit_register_tool_pages', [$this, 'registerToolPages']);
    }

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

    private function loadActiveTools(): void
    {
        // Charger les outils actifs
        foreach ($this->activeTools as $toolId => $isActive) {
            if ($isActive) {
                $this->loadTool($toolId);
            }
        }
    }

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

    private function getToolClassName(string $toolId): string
    {
        // Convertir elementor-block-analyzer en ElementorBlockAnalyzer
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $toolId)));
    }

    public function registerToolPages(string $parentSlug): void
    {
        // Ajouter un onglet pour chaque outil actif
        foreach ($this->tools as $toolId => $tool) {
            add_submenu_page(
                $parentSlug,
                $tool->getTitle(),
                $tool->getTitle(),
                'manage_options',
                $parentSlug . '-' . $toolId,
                [$this, 'renderToolPage']
            );
        }
    }

    public function renderToolPage(): void
    {
        // Obtenir l'ID de l'outil à partir de la page actuelle
        $screen = get_current_screen();
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        $toolId = str_replace('wp-debug-toolkit-tool-', '', $page);
        // Vérifier si l'outil existe
        if (isset($this->tools[$toolId])) {
            echo '<div class="wrap">';
            echo '<h1>' . esc_html($this->tools[$toolId]->getTitle()) . '</h1>';

            // Afficher le contenu de l'outil
            $this->tools[$toolId]->renderContent();

            echo '</div>';
        } else {
            echo '<div class="notice notice-error"><p>';
            echo __('Outil introuvable.', 'wp-debug-toolkit');
            echo '</p></div>';
        }
    }

    public function isToolActive(string $toolId): bool
    {
        return isset($this->activeTools[$toolId]) && $this->activeTools[$toolId];
    }

    public function setToolActive(string $toolId, bool $active = true): void
    {
        $this->activeTools[$toolId] = $active;
        update_option('wp_debug_toolkit_tools', $this->activeTools);
    }

    public function getTools(): array
    {
        return $this->tools;
    }
}