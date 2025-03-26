<?php
/**
 * Classe pour gérer les pages d'administration
 * @package WP_Debug_Toolkit
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe de base pour les pages d'administration
 */
class WpDebugToolkitAdminPage {

    protected string $pageId;
    protected string $pageTitle;

    /**
     * Constructeur
     * @param string $pageId Identifiant de la page
     * @param string $pageTitle Titre de la page
     */
    public function __construct(string $pageId, string $pageTitle)
    {
        $this->pageId = $pageId;
        $this->pageTitle = $pageTitle;

        // Ajouter les hooks
        $this->addHooks();
    }

    /**
     * Ajouter les hooks WordPress
     */
    protected function addHooks(): void
    {
        add_action('wp_debug_toolkit_tabs', [$this, 'registerTab']);
        add_action('wp_debug_toolkit_tab_content', [$this, 'tabContentDispatcher']);
    }

    /**
     * Enregistrer un onglet
     * @param array $tabs Liste des onglets existants
     * @return array Liste des onglets mise à jour
     */
    public function registerTab(array $tabs): array
    {
        $tabs[$this->pageId] = [
            'title' => $this->pageTitle,
            'icon' => $this->getTabIcon()
        ];
        return $tabs;
    }

    /**
     * Dispatcher pour le contenu de l'onglet
     * @param string $currentTab Onglet actif
     */
    public function tabContentDispatcher(string $currentTab): void
    {
        if ($currentTab === $this->pageId) {
            // Marquer l'action comme traitée
            do_action('wp_debug_toolkit_tab_content_' . $this->pageId);

            // Afficher le contenu
            $this->renderTabContent();
        }
    }

    /**
     * Récupérer l'icône de l'onglet
     * @return string Classe CSS de l'icône
     */
    protected function getTabIcon(): string
    {
        return 'dashicons-Admin-generic';
    }

    /**
     * Afficher le contenu de l'onglet
     */
    protected function renderTabContent(): void
    {
        // À surcharger dans les classes enfants
        echo '<div class="wrap">';
        echo '<h2>' . esc_html($this->pageTitle) . '</h2>';
        echo '<p>' . __('Contenu par défaut. Cette méthode doit être surchargée dans les classes enfants.', 'wp-debug-toolkit') . '</p>';
        echo '</div>';
    }

    /**
     * Afficher une notice
     * @param string $message Message à afficher
     * @param string $type Type de notice (error, warning, success, info)
     */
    protected function showNotice(string $message, string $type = 'info'): void
    {
        echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible">';
        echo '<p>' . esc_html($message) . '</p>';
        echo '</div>';
    }

    /**
     * Générer un nonce pour les actions AJAX ou les formulaires
     * @return string Nonce HTML
     */
    protected function getNonceField(): string
    {
        return wp_nonce_field('wp_debug_toolkit_' . $this->pageId . '_action', 'wp_debug_toolkit_nonce', true, false);
    }

    /**
     * Vérifier la validité d'un nonce
     * @param string $nonce Valeur du nonce à vérifier
     * @return bool True si le nonce est valide, false sinon
     */
    protected function verifyNonce(string $nonce): bool
    {
        return wp_verify_nonce($nonce, 'wp_debug_toolkit_' . $this->pageId . '_action');
    }
}