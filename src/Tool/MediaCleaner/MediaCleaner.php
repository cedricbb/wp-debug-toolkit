<?php
/**
 * Media Cleaner tool
 */

namespace WPDebugToolkit\Tool\MediaCleaner;

use WPDebugToolkit\Tool\AbstractTool;

class MediaCleaner extends AbstractTool
{

    protected function registerHooks(): void
    {
        // TODO: Implement registerHooks() method.
    }

    protected function getDefaultId(): string
    {
        // TODO: Implement getDefaultId() method.
        return 'media-cleaner';
    }

    protected function getDefaultTitle(): string
    {
        // TODO: Implement getDefaultTitle() method.
        return 'Nettoyeur de médias';
    }

    protected function getDefaultDescription(): string
    {
        // TODO: Implement getDefaultDescription() method.
        return '';
    }

    protected function getDefaultIcon(): string
    {
        // TODO: Implement getDefaultIcon() method.
        return 'dashicons-images-alt2';
    }

    public function renderContent(): void
    {
        // TODO: Implement renderContent() method.
    }
}