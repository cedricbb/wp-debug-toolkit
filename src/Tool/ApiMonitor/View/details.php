<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$status_class = floor($details['response_code'] / 100) . 'xx';
?>

<div class="wp-debug-toolkit-api-call-details">
    <!-- En-tête des détails -->
    <div class="details-header">
        <div class="details-meta">
            <h2>
                <span class="method-badge method-<?php echo strtolower($details['method']); ?>">
                    <?php echo esc_html($details['method']); ?>
                </span>
                <span class="endpoint-path"><?php echo esc_html($details['endpoint']); ?></span>
            </h2>
            <div class="details-info">
                <span class="status-badge status-<?php echo $status_class; ?>">
                    <?php echo esc_html($details['response_code']); ?>
                </span>
                <span class="response-time">
                    <?php echo number_format($details['response_time'] * 1000, 1); ?> ms
                </span>
                <span class="timestamp">
                    <?php echo esc_html(wp_date(
                        get_option('date_format') . ' ' . get_option('time_format'),
                        strtotime($details['created_at'])
                    )); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Navigation des onglets -->
    <div class="details-tabs">
        <button class="tab-button active" data-tab="request">
            <?php _e('Requête', 'wp-debug-toolkit'); ?>
        </button>
        <button class="tab-button" data-tab="response">
            <?php _e('Réponse', 'wp-debug-toolkit'); ?>
        </button>
        <button class="tab-button" data-tab="headers">
            <?php _e('Headers', 'wp-debug-toolkit'); ?>
        </button>
        <button class="tab-button" data-tab="curl">
            <?php _e('cURL', 'wp-debug-toolkit'); ?>
        </button>
    </div>

    <!-- Contenu des onglets -->
    <div class="details-content">
        <!-- Onglet Requête -->
        <div id="request-panel" class="tab-panel active">
            <div class="details-section">
                <h3><?php _e('URL', 'wp-debug-toolkit'); ?></h3>
                <div class="url-display">
                    <code><?php echo esc_html(get_rest_url(null, $details['endpoint'])); ?></code>
                    <button class="copy-button" data-clipboard-text="<?php echo esc_attr(get_rest_url(null, $details['endpoint'])); ?>">
                        <span class="dashicons dashicons-clipboard"></span>
                    </button>
                </div>
            </div>

            <?php if (!empty($details['request_body'])): ?>
                <div class="details-section">
                    <h3><?php _e('Corps de la requête', 'wp-debug-toolkit'); ?></h3>
                    <div class="code-display">
                        <pre><code class="language-json"><?php
                                echo esc_html(json_encode($details['request_body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                                ?></code></pre>
                        <button class="copy-button" data-clipboard-text="<?php
                        echo esc_attr(json_encode($details['request_body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                        ?>">
                            <span class="dashicons dashicons-clipboard"></span>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Onglet Réponse -->
        <div id="response-panel" class="tab-panel">
            <div class="details-section">
                <div class="response-meta">
                    <div class="status-info status-<?php echo $status_class; ?>">
                        <span class="status-code"><?php echo esc_html($details['response_code']); ?></span>
                        <span class="status-text"><?php
                            echo esc_html(get_status_header_desc($details['response_code']));
                            ?></span>
                    </div>
                    <div class="timing-info">
                        <span class="response-time <?php echo $details['response_time'] > 1 ? 'slow' : ''; ?>">
                            <?php echo number_format($details['response_time'] * 1000, 1); ?> ms
                        </span>
                    </div>
                </div>
            </div>

            <?php if (!empty($details['response_body'])): ?>
                <div class="details-section">
                    <h3><?php _e('Corps de la réponse', 'wp-debug-toolkit'); ?></h3>
                    <div class="code-display">
                       <pre><code class="language-json"><?php
                               // Encode en JSON avec les options de formatage
                               $json = json_encode($details['response_body'],
                                   JSON_PRETTY_PRINT |
                                   JSON_UNESCAPED_SLASHES |
                                   JSON_UNESCAPED_UNICODE
                               );
                               // Convertit les séquences Unicode en caractères
                               $json = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($matches) {
                                   return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UCS-2BE');
                               }, $json);
                               echo esc_html($json);
                               ?></code></pre>
                        <button class="copy-button" data-clipboard-text="<?php
                        echo esc_attr(json_encode($details['response_body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                        ?>">
                            <span class="dashicons dashicons-clipboard"></span>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Onglet Headers -->
        <div id="headers-panel" class="tab-panel">
            <div class="details-section">
                <h3><?php _e('Headers de la requête', 'wp-debug-toolkit'); ?></h3>
                <table class="widefat">
                    <thead>
                    <tr>
                        <th><?php _e('Header', 'wp-debug-toolkit'); ?></th>
                        <th><?php _e('Valeur', 'wp-debug-toolkit'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($details['request_headers'] as $header => $value): ?>
                        <tr>
                            <td><?php echo esc_html($header); ?></td>
                            <td><?php echo esc_html(is_array($value) ? implode(', ', $value) : $value); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($details['response_headers'])): ?>
                <div class="details-section">
                    <h3><?php _e('Headers de réponse', 'wp-debug-toolkit'); ?></h3>
                    <table class="widefat">
                        <thead>
                        <tr>
                            <th><?php _e('Header', 'wp-debug-toolkit'); ?></th>
                            <th><?php _e('Valeur', 'wp-debug-toolkit'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($details['response_headers'] as $header => $value): ?>
                            <tr>
                                <td><?php echo esc_html($header); ?></td>
                                <td><?php echo esc_html(is_array($value) ? implode(', ', $value) : $value); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Onglet cURL -->
        <div id="curl-panel" class="tab-panel">
            <div class="details-section">
                <h3><?php _e('Commande cURL', 'wp-debug-toolkit'); ?></h3>
                <div class="code-display">
                    <pre><code class="language-bash"><?php
                            $curlCommand = "curl -X " . $details['method'];
                            // Ajout des headers
                            if (!empty($details['request_headers'])) {
                                foreach ($details['request_headers'] as $header => $value) {
                                    if (is_array($value)) {
                                        foreach ($value as $v) {
                                            $curlCommand .= sprintf("\n  -H '%s: %s'", $header, $v);
                                        }
                                    } else {
                                        $curlCommand .= sprintf("\n  -H '%s: %s'", $header, $value);
                                    }
                                }
                            }
                            // Ajout du body si présent
                            if (!empty($details['request_body'])) {
                                $curlCommand .= "\n  -d '" . json_encode($details['request_body']) . "'";
                            }
                            // Ajout de l'URL
                            $curlCommand .= "\n  '" . get_rest_url(null, $details['endpoint']) . "'";
                            echo esc_html($curlCommand);
                            ?></code></pre>
                    <button class="copy-button" data-clipboard-text="<?php echo esc_attr($curlCommand); ?>">
                        <span class="dashicons dashicons-clipboard"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
