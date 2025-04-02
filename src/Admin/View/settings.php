<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="" class="settings-form">
        <?php wp_nonce_field('wp_debug_toolkit_settings', 'wp_debug_toolkit_settings_nonce'); ?>

        <div class="metabox-holder">
            <!-- Paramètres généraux -->
            <div class="postbox">
                <h2 class="hndle"><span><?php _e('Paramètres généraux', 'wp-debug-toolkit'); ?></span></h2>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="acces_level"><?php _e('Niveau d\'accès requis', 'wp-debug-toolkit'); ?></label>
                            </th>
                            <td>
                                <select name="acces_level" id="acces_level">
                                    <option value="manage_options" <?php selected($settings['acces_level'], 'manage_options'); ?>><?php _e('Administrateur', 'wp-debug-toolkit'); ?></option>
                                    <option value="edit_pages" <?php selected($settings['acces_level'], 'edit_pages'); ?>><?php _e('Éditeur', 'wp-debug-toolkit'); ?></option>
                                    <option value="publish_posts" <?php selected($settings['acces_level'], 'publish_posts'); ?>><?php _e('Auteur', 'wp-debug-toolkit'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Journalisation', 'wp-debug-toolkit'); ?></th>
                            <td>
                                <label for="enable_logging">
                                    <input type="checkbox" name="enable_logging" id="enable_logging" <?php checked($settings['enable_logging']); ?>>
                                    <?php _e('Activer la journalisation des actions', 'wp-debug-toolkit'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Mode développeur', 'wp-debug-toolkit'); ?></th>
                            <td>
                                <label for="developer_mode">
                                    <input type="checkbox" name="developer_mode" id="developer_mode" <?php checked($settings['developer_mode']); ?>>
                                    <?php _e('Activer les fonctionnalités avancées de développement', 'wp-debug-toolkit'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Gestion des outils -->
            <div class="postbox">
                <h2 class="hndle"><span><?php _e('Activer/Désactiver les outils', 'wp-debug-toolkit'); ?></span></h2>
                <div class="inside">
                    <p><?php _e('Utilisez les switches ci-dessous pour activer ou désactiver les outils disponibles.', 'wp-debug-toolkit'); ?></p>

                    <div class="wp-debug-toolkit-tools-switches">
                        <?php foreach ($allTools as $toolId => $tool): ?>
                            <div class="wp-debug-toolkit-tool-switch" data-tool-id="<?php echo esc_attr($toolId); ?>">
                                <div class="switch-row">
                                    <label class="switch">
                                        <input type="checkbox" id="tool-<?php echo esc_attr($toolId); ?>" name="active_tools[]" value="<?php echo esc_attr($toolId); ?>" <?php checked(isset($activeTools[$toolId]) && $activeTools[$toolId]); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <span class="status" id="status-<?php echo esc_attr($toolId); ?>"><?php echo (isset($activeTools[$toolId]) && $activeTools[$toolId]) ? __('On', 'wp-debug-toolkit') : __('Off', 'wp-debug-toolkit'); ?></span>
                                    <span class="switch-label">
                                        <strong><?php echo esc_html($tool['title']); ?></strong><br>
                                        <span class="description"><?php echo esc_html($tool['description']); ?></span>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <p class="submit">
            <input type="submit" name="wp_debug_toolkit_settings_submit" id="submit" class="button button-crayola" value="<?php _e('Enregistrer les modifications', 'wp-debug-toolkit'); ?>">
        </p>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialiser chaque switch
        document.querySelectorAll('.wp-debug-toolkit-tool-switch').forEach(function(switchContainer) {
            const toolId = switchContainer.dataset.toolId;
            const checkbox = document.getElementById('tool-' + toolId);
            const statusElement = document.getElementById('status-' + toolId);

            // Mettre à jour le statut initial
            updateStatus(checkbox, statusElement);

            // Ajouter l'événement de changement
            checkbox.addEventListener('change', function() {
                updateStatus(this, statusElement);
            });
        });

        function updateStatus(checkbox, statusElement) {
            statusElement.textContent = checkbox.checked ? 'On' : 'Off';
        }
    });
</script>