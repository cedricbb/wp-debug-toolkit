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
    private DatabaseManager $database;
    private ApiAnalyzer $analyzer;
    private ApiLogger $logger;

    public function __construct()
    {
        // Initialiser la base de données
        $this->database = new DatabaseManager();
        $this->analyzer = new ApiAnalyzer($this->database);
        $this->logger = new ApiLogger($this->database);

        // Appeler le constructeur parent
        parent::__construct();

        // Initialiser les hooks
        $this->initHooks();
    }

    private function initHooks(): void
    {
        // TODO: Implement initHooks() method.
    }
}
