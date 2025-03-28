<?php

namespace WPDebugToolkit\Admin\Page;

abstract class AbstractPage
{
    protected string $id;
    protected string $title;
    protected string $icon;

    public function __construct(string $id = '', string $title = '', string $icon = '')
    {
        $this->id = $id ?: $this->getId();
        $this->title = $title ?: $this->getTitle();
        $this->icon = $icon ?: $this->getIcon();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getIcon(): string
    {
        return $this->icon ?: 'dashicons-admin-generic';
    }

    abstract public function render(): void;

    protected function showNotice(string $message, string $type = 'info'): void
    {
        echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible">';
        echo '<p>' . esc_html($message) . '</p>';
        echo '</div>';
    }

    protected function getNonceField(): string
    {
        return wp_nonce_field('wp_debug_toolkit_' . $this->id . '_action', 'wp_debug_toolkit_nonce', true, false);
    }

    protected function verifyNonce(string $nonce): bool
    {
        return wp_verify_nonce($nonce, 'wp_debug_toolkit_' . $this->id . '_action');
    }
}
