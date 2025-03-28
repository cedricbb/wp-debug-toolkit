<?php
/**
 * Cron Monitor tool
 */

namespace WPDebugToolkit\Tool\CronMonitor;

use WPDebugToolkit\Tool\AbstractTool;

/**
 * Cron Monitor tool
 */
class CronMonitor extends AbstractTool
{

    protected function registerHooks(): void
    {
        // TODO: Implement registerHooks() method.
    }

    protected function getDefaultId(): string
    {
        // TODO: Implement getDefaultId() method.
        return 'cron-monitor';
    }

    protected function getDefaultTitle(): string
    {
        // TODO: Implement getDefaultTitle() method.
        return 'Moniteur de Cron';
    }

    protected function getDefaultDescription(): string
    {
        // TODO: Implement getDefaultDescription() method.
        return '';
    }

    protected function getDefaultIcon(): string
    {
        // TODO: Implement getDefaultIcon() method.
        return 'dashicons-clock';
    }

    public function renderContent(): void
    {
        // TODO: Implement renderContent() method.
    }
}
