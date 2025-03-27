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
        add_filter('wp_debug_toolkit_tabs', [$this, 'registerToolTabs']);
        add_action('wp_debug_toolkit_tab_content', [$this, 'renderToolContent']);
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
        $fullClassName = 'WPDebugToolkit\\Tools\\' . $className . '\\' . $className;

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

    public function registerToolTabs(array $tabs): array
    {
        // Ajouter un onglet pour chaque outil actif
        foreach ($this->tools as $toolId => $tool) {
            $tabs[$toolId] = [
                'title' => $tool->getTitle(),
                'icon' => $tool->getIcon()
            ];
        }

        return $tabs;
    }

    public function renderToolContent(string $currentTab): void
    {
        // Vérifier si l'onglet correspond à un outil
        if (isset($this->tools[$currentTab])) {
            // Afficher le contenu de l'outil
            $this->tools[$currentTab]->renderContent();
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