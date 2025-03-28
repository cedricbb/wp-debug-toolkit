<?php

namespace WPDebugToolkit\Admin\Page;

class About extends AbstractPage
{
    public function __construct()
    {
        parent::__construct('about', __('À propos', 'wp-debug-toolkit'), 'dashicons-info');
    }

    public function render(): void
    {
        // Inclure la vue À propos
        require_once WP_DEBUG_TOOLKIT_PLUGIN_DIR . 'src/Admin/View/about.php';
    }
}
