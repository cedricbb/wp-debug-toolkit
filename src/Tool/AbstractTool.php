<?php

namespace WPDebugToolkit\Tool;

abstract class AbstractTool implements ToolInterface
{
    protected string $id;
    protected string $title;
    protected string $description;
    protected string $icon;

    public function __construct()
    {
        // Initialiser les propriétés avec les valeurs par défaut
        $this->id = $this->getDefaultId();
        $this->title = $this->getDefaultTitle();
        $this->description = $this->getDefaultDescription();
        $this->icon = $this->getDefaultIcon();
    }

    public function init(): void
    {
        // Enregistrer les hooks spécifiques à l'outil
        $this->registerHooks();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * Enregistrer les hooks spécifiques à l'outil
     */
    abstract protected function registerHooks(): void;

    /**
     * Récupérer l'ID par défaut de l'outil
     */
    abstract protected function getDefaultId(): string;

    /**
     * Récupérer le titre par défaut de l'outil
     */
    abstract protected function getDefaultTitle(): string;

    /**
     * Récupérer la description par défaut de l'outil
     */
    abstract protected function getDefaultDescription(): string;

    /**
     * Récupérer l'icône par défaut de l'outil
     */
    abstract protected function getDefaultIcon(): string;

    /**
     * Afficher le contenu de l'outil
     */
    abstract public function renderContent(): void;

    /**
     * Afficher une notice
     */
    protected function showNotice(string $message, string $type = 'info'): void
    {
        echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible">';
        echo '<p>' . esc_html($message) . '</p>';
        echo '</div>';
    }

    /**
     * Générer un nonce pour les actions AJAX ou les formulaires
     */
    protected function getNonceField(): string
    {
        return wp_nonce_field('wp_debug_toolkit_' . $this->id . '_action', 'wp_debug_toolkit_nonce', true, false);
    }

    /**
     * Vérifier la validité d'un nonce
     */
    protected function verifyNonce(string $nonce): bool
    {
        return wp_verify_nonce($nonce, 'wp_debug_toolkit_' . $this->id . '_action');
    }
}