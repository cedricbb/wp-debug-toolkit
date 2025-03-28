<?php
/**
 * Cache Inspector tool
 */

namespace WPDebugToolkit\Tool\CacheInspector;

use WPDebugToolkit\Tool\AbstractTool;

/**
 * Cache Inspector tool
 */
class CacheInspector extends AbstractTool
{

    protected function registerHooks(): void
    {
        // TODO: Implement registerHooks() method.
    }

    protected function getDefaultId(): string
    {
        // TODO: Implement getDefaultId() method.
        return 'cache-inspector';
    }

    protected function getDefaultTitle(): string
    {
        // TODO: Implement getDefaultTitle() method.
        return 'Inspecteur de cache';
    }

    protected function getDefaultDescription(): string
    {
        // TODO: Implement getDefaultDescription() method.
        return '';
    }

    protected function getDefaultIcon(): string
    {
        // TODO: Implement getDefaultIcon() method.
        return 'dashicons-performance';
    }

    public function renderContent(): void
    {
        // TODO: Implement renderContent() method.
    }
}
