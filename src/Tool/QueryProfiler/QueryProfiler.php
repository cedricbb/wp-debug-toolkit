<?php
/**
 * Query Profiler tool
 */

namespace WPDebugToolkit\Tool\QueryProfiler;

use WPDebugToolkit\Tool\AbstractTool;

class QueryProfiler extends AbstractTool
{

    protected function registerHooks(): void
    {
        // TODO: Implement registerHooks() method.
    }

    protected function getDefaultId(): string
    {
        // TODO: Implement getDefaultId() method.
        return 'query-profiler';
    }

    protected function getDefaultTitle(): string
    {
        // TODO: Implement getDefaultTitle() method.
        return 'Query Profiler';
    }

    protected function getDefaultDescription(): string
    {
        // TODO: Implement getDefaultDescription() method.
        return 'Monitor database queries';
    }

    protected function getDefaultIcon(): string
    {
        // TODO: Implement getDefaultIcon() method.
        return 'dashicons-database';
    }

    public function renderContent(): void
    {
        // TODO: Implement renderContent() method.
    }
}