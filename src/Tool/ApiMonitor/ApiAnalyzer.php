<?php

namespace WPDebugToolkit\Tool\ApiMonitor;

use JsonException;
use WPDebugToolkit\Util\DatabaseManager;

final class ApiAnalyzer
{
    private DatabaseManager $database;

    public function __construct(DatabaseManager $database)
    {
        $this->database = $database;
    }

    public function getStats(): array
    {
        global $wpdb;
        $table = $this->database->getTableName();

        return [
            'total_calls' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table"),
            'total_errors' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE response_code >= 400"),
            'avg_response_time' => (float) $wpdb->get_var("SELECT AVG(response_time) FROM $table"),
            'calls_today' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE DATE(created_at) = %s", date('Y-m-d'))),
            'avg_response_time_24h' => (float) $wpdb->get_var($wpdb->prepare("SELECT AVG(response_time) FROM $table WHERE created_at >= %s", date('Y-m-d H:i:s', strtotime('-24 hours'))))
        ];
    }

    public function getRecentCalls(int $limit = 10): array
    {
        global $wpdb;
        $table = $this->database->getTableName();

        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table ORDER BY created_at DESC LIMIT %d", $limit), ARRAY_A);
    }

    public function getSlowestCalls(int $limit = 10): array
    {
        global $wpdb;
        $table = $this->database->getTableName();

        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE response_time >= 1 ORDER BY response_time DESC LIMIT %d", $limit), ARRAY_A);
    }

    public function getErrorCalls(int $limit = 10): array
    {
        global $wpdb;
        $table = $this->database->getTableName();

        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE response_code >= 400 ORDER BY response_code DESC LIMIT %d", $limit), ARRAY_A);
    }

    public function getCallDetails(int $logId): ?array
    {
        global $wpdb;
        $table = $this->database->getTableName();

        $details = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $logId), ARRAY_A);

        if ($details) {
            try {
                $details['request_headers'] = json_decode($details['request_headers'], true, 512, JSON_THROW_ON_ERROR);
                $details['request_body'] = json_decode($details['request_body'], true, 512, JSON_THROW_ON_ERROR);
                $details['response_headers'] = json_decode($details['response_headers'], true, 512, JSON_THROW_ON_ERROR);
                $details['response_body'] = json_decode($details['response_body'], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                error_log('JSON decode error: ' . $e->getMessage());
            }
        }

        return $details;
    }
}
