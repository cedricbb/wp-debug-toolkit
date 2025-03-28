<?php
/**
 * API Monitor tool
 * @package WPDebugToolkit
 */

namespace WPDebugToolkit\Tool\ApiMonitor;

use WPDebugToolkit\Tool\AbstractTool;

/**
 * API Monitor tool
 */
class ApiMonitor extends AbstractTool
{

    protected function registerHooks(): void
    {
        // TODO: Implement registerHooks() method.
    }

    protected function getDefaultId(): string
    {
        // TODO: Implement getDefaultId() method.
        return 'api-monitor';
    }

    protected function getDefaultTitle(): string
    {
        // TODO: Implement getDefaultTitle() method.
        return 'Moniteur d\'API';
    }

    protected function getDefaultDescription(): string
    {
        // TODO: Implement getDefaultDescription() method.
        return '';
    }

    protected function getDefaultIcon(): string
    {
        // TODO: Implement getDefaultIcon() method.
        return 'dashicons-rest-api';
    }

    public function renderContent(): void
    {
        // TODO: Implement renderContent() method.
    }
}