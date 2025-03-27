<?php

declare(strict_types=1);


namespace WPDebugToolkit\Admin\Page;


class ElementorBlockAnalyzer extends AbstractPage
{

    public function __construct()
    {
        parent::__construct('elementor-block-analyzer', __('Analyseur de blocks Elementor', 'wp-debug-toolkit'), 'dashicons-welcome-widgets-menus');
    }

    public function render(): void
    {
        // TODO: Implement render() method.
    }
}