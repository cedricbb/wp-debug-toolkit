<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap wp-debug-toolkit-api-monitor">
    <!-- Cards Statistiques -->
    <div class="wp-debug-toolkit-api-stats-grid">
        <div class="wp-debug-toolkit-api-stat-card">
            <div class="stat-header">
                <span class="dashicons dashicons-chart-bar"></span>
                <h3><?php _e('Total des appels', 'wp-debug-toolkit'); ?></h3>
            </div>
            <div class="stat-value"><?php echo esc_html(number_format($stats['total_calls'])); ?></div>
            <div class="stat-footer">
                <?php
                $today = $stats['calls_today'];
                $change = $stats['total_calls'] > 0 ? ($today / $stats['total_calls']) * 100 : 0;
                echo sprintf(
                    __('%s aujourd\'hui (%s%%)', 'wp-debug-toolkit'),
                    number_format($today),
                    number_format($change, 1)
                );
                ?>
            </div>
        </div>
        <div class="wp-debug-toolkit-api-stat-card">
            <div class="stat-header">
                <span class="dashicons dashicons-clock"></span>
                <h3><?php _e('Temps de réponse moyen', 'wp-debug-toolkit'); ?></h3>
            </div>
            <div class="stat-value">
                <?php echo number_format($stats['avg_response_time'] * 1000, 1); ?><span class="unit">ms</span>
            </div>
            <div class="stat-footer">
                <?php
                $diff = $stats['avg_response_time'] - $stats['avg_response_time_24h'];
                $trend = $diff < 0 ? 'positive' : ($diff > 0 ? 'negative' : 'neutral');
                $diff_text = abs($diff) * 1000;
                echo sprintf(
                    __('%s ms sur 24h', 'wp-debug-toolkit'),
                    number_format($stats['avg_response_time_24h'] * 1000, 1)
                );
                ?>
                <span class="trend <?php echo $trend; ?>">
                    <?php echo $trend === 'positive' ? '↓' : ($trend === 'negative' ? '↑' : '→'); ?>
                    <?php echo number_format($diff_text, 1); ?>ms
                </span>
            </div>
        </div>
        <div class="wp-debug-toolkit-api-stat-card <?php echo $stats['total_errors'] > 0 ? 'has-errors' : ''; ?>">
            <div class="stat-header">
                <span class="dashicons dashicons-warning"></span>
                <h3><?php _e('Erreurs', 'wp-debug-toolkit'); ?></h3>
            </div>
            <div class="stat-value"><?php echo esc_html(number_format($stats['total_errors'])); ?></div>
            <div class="stat-footer">
                <?php
                $error_rate = $stats['total_calls'] > 0
                    ? ($stats['total_errors'] / $stats['total_calls']) * 100
                    : 0;
                echo sprintf(__('Taux d\'erreur: %s%%', 'wp-debug-toolkit'), number_format($error_rate, 2));
                ?>
            </div>
        </div>
    </div>

    <!-- Barre d'actions -->
    <div class="wp-debug-toolkit-api-toolbar">
        <div class="wp-debug-toolkit-api-filters">
            <select id="endpoint-filter" name="endpoint">
                <option value=""><?php _e('Tous les endpoints', 'wp-debug-toolkit'); ?></option>
                <?php foreach ($endpoints as $endpoint): ?>
                    <option value="<?php echo esc_attr($endpoint); ?>" <?php selected($endpoint_filter, $endpoint); ?>>
                        <?php echo esc_html($endpoint); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select id="status-filter" name="status">
                <option value=""><?php _e('Tous les statuts', 'wp-debug-toolkit'); ?></option>
                <option value="success" <?php selected($status_filter, 'success'); ?>>
                    <?php _e('Succès (2xx)', 'wp-debug-toolkit'); ?>
                </option>
                <option value="redirect" <?php selected($status_filter, 'redirect'); ?>>
                    <?php _e('Redirection (3xx)', 'wp-debug-toolkitd'); ?>
                </option>
                <option value="error" <?php selected($status_filter, 'error'); ?>>
                    <?php _e('Erreur (4xx/5xx)', 'wp-debug-toolkit'); ?>
                </option>
            </select>
            <button id="apply-filters" class="button">
                <span class="dashicons dashicons-filter"></span>
                <?php _e('Appliquer les filtres', 'wp-debug-toolkit'); ?>
            </button>

            <?php if (!empty($endpoint_filter) || !empty($status_filter)): ?>
                <a href="<?php echo esc_url(remove_query_arg(['endpoint', 'status'])); ?>" class="button">
                    <span class="dashicons dashicons-dismiss"></span>
                    <?php _e('Réinitialiser les filtres', 'wp-debug-toolkit'); ?>
                </a>
            <?php endif; ?>
        </div>

        <div class="wp-debug-toolkit-api-actions">
            <button id="refresh-data" class="button">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Actualiser', 'wp-debug-toolkit'); ?>
            </button>
            <button id="clear-logs" class="button button-warning">
                <span class="dashicons dashicons-trash"></span>
                <?php _e('Effacer les logs', 'wp-debug-toolkit'); ?>
            </button>
        </div>
    </div>

    <!-- Tabs de navigation -->
    <div class="wp-debug-toolkit-api-tabs">
        <a href="<?php echo add_query_arg('view', 'recent'); ?>"
           class="tab-button <?php echo empty($_GET['view']) || $_GET['view'] === 'recent' ? 'active' : ''; ?>">
            <?php _e('Appels Récents', 'wp-debug-toolkit'); ?>
        </a>
        <a href="<?php echo add_query_arg('view', 'slow'); ?>"
           class="tab-button <?php echo isset($_GET['view']) && $_GET['view'] === 'slow' ? 'active' : ''; ?>">
            <?php _e('Appels Lents', 'wp-debug-toolkit'); ?>
        </a>
        <a href="<?php echo add_query_arg('view', 'error'); ?>"
           class="tab-button <?php echo isset($_GET['view']) && $_GET['view'] === 'errors' ? 'active' : ''; ?>">
            <?php _e('Erreurs', 'wp-debug-toolkit'); ?>
            <?php if ($stats['total_errors'] > 0): ?>
                <span class="error-badge"><?php echo $stats['total_errors']; ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- Conteneur des tableaux -->
    <div class="wp-debug-toolkit-api-tab-content">
        <?php
        $current_view = $_GET['view'] ?? 'recent';

        // Sélectionner les données en fonction de la vue
        $active_calls = match ($current_view) {
            'slow' => $slowestCalls,
            'error' => $errorCalls,
            default => $recentCalls,
        };
        ?>
        <?php if (empty($active_calls)): ?>
            <div class="wp-debug-toolkit-api-no-data">
                <?php
                switch ($current_view) {
                    case 'slow':
                        ?>
                        <span class="dashicons dashicons-performance"></span>
                        <p><?php _e('Aucun appel API lent à afficher.', 'wp-debug-toolkit'); ?></p>
                        <?php
                        break;
                    case 'error':
                        ?>
                        <span class="dashicons dashicons-warning"></span>
                        <p><?php _e('Aucun appel API en erreur à afficher.', 'wp-debug-toolkit'); ?></p>
                        <?php
                        break;
                    default:
                        ?>
                        <span class="dashicons dashicons-clock"></span>
                        <p><?php _e('Aucun appel API récent à afficher.', 'wp-debug-toolkit'); ?></p>
                    <?php
                }
                ?>
            </div>
        <?php else: ?>
            <!-- Tableau des appels récents -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                <tr>
                    <th class="column-datetime"><?php _e('Date/Heure', 'wp-debug-toolkit'); ?></th>
                    <th class="column-endpoint"><?php _e('Endpoint', 'wp-debug-toolkit'); ?></th>
                    <th class="column-method"><?php _e('Méthode', 'wp-debug-toolkit'); ?></th>
                    <th class="column-status"><?php _e('Statut', 'wp-debug-toolkit'); ?></th>
                    <th class="column-time"><?php _e('Temps', 'wp-debug-toolkit'); ?></th>
                    <th class="column-actions"><?php _e('Actions', 'wp-debug-toolkit'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($active_calls as $call): ?>
                    <tr>
                        <td>
                            <?php echo esc_html(wp_date(
                                get_option('date_format') . ' ' . get_option('time_format'),
                                strtotime($call['created_at'])
                            )); ?>
                        </td>
                        <td class="column-endpoint">
                            <span class="endpoint-path"><?php echo esc_html($call['endpoint']); ?></span>
                        </td>
                        <td class="column-method">
                            <span class="method-badge method-<?php echo strtolower($call['method']); ?>">
                                <?php echo esc_html($call['method']); ?>
                            </span>
                        </td>
                        <td class="column-status">
                            <span class="status-badge status-<?php echo floor($call['response_code'] / 100); ?>xx">
                                <?php echo esc_html($call['response_code']); ?>
                            </span>
                        </td>
                        <td class="column-time">
                            <span class="response-time <?php echo $call['response_time'] > 1 ? 'slow' : ''; ?>">
                                <?php echo number_format($call['response_time'] * 1000, 1); ?> ms
                            </span>
                        </td>
                        <td class="column-actions">
                            <button class="button button-small view-details"
                                    data-log-id="<?php echo esc_attr($call['id']); ?>"
                                    type="button">
                                <span class="dashicons dashicons-visibility"></span>
                                <?php _e('Détails', 'wp-debug-toolkit'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<!-- Template pour la modale de détails -->
<script type="text/template" id="tmpl-api-details">
    <div id="api-call-details-{{ data.id }}" class="api-call-details loading">
        <div class="loading-spinner"></div>
    </div>
</script>
<div id="api-call-details" style="display:none;">
    <div id="api-call-details-content"></div>
</div>
