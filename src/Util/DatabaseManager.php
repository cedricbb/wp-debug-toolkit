<?php

namespace WPDebugToolkit\Util;

final class DatabaseManager
{
   private string $table;
   
   public function __construct()
   {
    global $wpdb;
    $this->table = $wpdb->prefix . 'debug_toolkit_api_logs';
   }

   public function createTable(): void
   {
    if(empty($this->table)) {
        return;
    }
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS %1s (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            endpoint varchar(255) NOT NULL,
            method varchar(10) NOT NULL,
            request_headers longtext,
            request_body longtext,
            status_code smallint unsigned,
            response_code smallint unsigned,
            response_headers longtext,
            response_body longtext,
            response_time float,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY endpoint (endpoint),
            KEY method (method),
            KEY response_code (response_code),
            KEY created_at (created_at)
    ) %1s;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $sql = $wpdb->prepare($sql, $this->table, $charset_collate);
    $wpdb->query($sql);
   }

   public function getTableName(): string
   {
    return $this->table;
   }

   public function clearLogs(): void
   {
    global $wpdb;
    $wpdb->query("TRUNCATE TABLE {$this->table}");
   }

   public function cleanOldLogs(int $days = 30): void
   {
    global $wpdb;
    $wpdb->query($wpdb->prepare("DELETE FROM {$this->table} WHERE created_at < %s", date('Y-m-d H:i:s', strtotime("-$days days"))));
   }

   public function dropTable(): void
   {
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS {$this->table}");
   }

   public function tableExists(): bool
   {
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE '{$this->table}'") === $this->table);
   }

   public function optimizeTable(): void
   {
    global $wpdb;
    $wpdb->query("OPTIMIZE TABLE {$this->table}");
   }

   public function insert(array $data): bool
   {
    global $wpdb;
    return $wpdb->insert($this->table, $data) !== false;
   }
}
