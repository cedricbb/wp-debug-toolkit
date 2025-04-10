<?php

namespace WPDebugToolkit\Tool\ApiMonitor;

use WP_REST_Server;
use WP_REST_Response;
use WP_REST_Request;
use WPDebugToolkit\Util\DatabaseManager;

final class ApiLogger
{
    private DatabaseManager $database;

    public function __construct(DatabaseManager $database)
    {
        $this->database = $database;
    }

    public function logPreDispatch($result, WP_REST_Server $server, WP_REST_Request $request): mixed
    {
        if ($result !== null) {
            return $result;
        }

        $this->startTimer();
        return null;
    }

    public function logPostDispatch($response, WP_REST_Server $server, WP_REST_Request $request): WP_REST_Response
    {
        if (!$this->shouldLogRequest($request)) {
            return $response;
        }

        $responseTime = $this->stopTimer();

        $data = [
            'endpoint' => $request->get_route(),
            'method' => $request->get_method(),
            'request_headers' => json_encode($request->get_headers()),
            'request_body' => json_encode($request->get_body()),
            'response_code' => $response->get_status(),
            'response_headers' => json_encode($response->get_headers()),
            'response_body' => json_encode($response->get_data()),
            'response_time' => $responseTime,
            'created_at' => current_time('mysql'),
        ];

        $this->database->insert($data);

        return $response;
    }

    private function startTimer(): void
    {
        global $api_monitor_start_time;
        $api_monitor_start_time = microtime(true);
    }

    private function stopTimer(): float
    {
        global $api_monitor_start_time;
        $api_monitor_end_time = microtime(true);
        return $api_monitor_end_time - ($api_monitor_start_time ?? $api_monitor_end_time);
    }

    private function shouldLogRequest(WP_REST_Request $request): bool
    {
        return !in_array($request->get_route(), $this->getIgnoredEndpoints(), true);
    }

    private function getIgnoredEndpoints(): array
    {
        return [
            '/wp-debug-toolkit/v1/monitor',
            '/wp/v2/settings'
        ];
    }
}
