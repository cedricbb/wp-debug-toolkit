/**
 * Script pour la personnalisation du tableau de bord WP Debug Toolkit
 */
(function($) {
    'use strict';

    // Objet principal du customizer
    var WPDebugToolkitCustomizer = {
        /**
         * Initialisation
         */
        init: function() {
            this.initSortable();
            this.initMetaboxToggles();
            this.initScreenOptions();
            this.bindEvents();
        },

        /**
         * Initialise la fonctionnalité sortable
         */
        initSortable: function() {
            $('.wp-debug-toolkit-tools-list').sortable({
                placeholder: 'wp-debug-toolkit-tool-card-placeholder',
                handle: '.wp-debug-toolkit-tool-card-header',
                cursor: 'move',
                opacity: 0.65,
                revert: 300,
                delay: 150,
                tolerance: 'pointer',
                update: function(event, ui) {
                    WPDebugToolkitCustomizer.saveToolsOrder();
                }
            }).disableSelection();
        },

        /**
         * Initialise les toggles pour montrer/cacher des outils
         */
        initMetaboxToggles: function() {
            // Ajouter des icônes de toggle dans les en-têtes d'outils
            $('.wp-debug-toolkit-tool-card-header').append(
                '<button class="wp-debug-toolkit-tool-toggle dashicons dashicons-visibility" aria-expanded="true"></button>'
            );

            // Appliquer l'état sauvegardé (visible/caché) pour chaque outil
            $('.wp-debug-toolkit-tool-card').each(function() {
                var toolId = $(this).data('tool-id');
                var isHidden = WPDebugToolkitCustomizer.getToolUserPreference(toolId, 'hidden');

                if (isHidden) {
                    $(this).addClass('wp-debug-toolkit-tool-hidden');
                    $(this).find('.wp-debug-toolkit-tool-toggle')
                        .removeClass('dashicons-visibility')
                        .addClass('dashicons-hidden')
                        .attr('aria-expanded', 'false');
                }
            });
        },

        /**
         * Initialise les options d'écran pour activer/désactiver les outils
         */
        initScreenOptions: function() {
            // Ajouter un bouton pour les options d'écran s'il n'existe pas déjà
            if ($('#screen-options-link-wrap').length === 0) {
                $('body').append(
                    '<div id="screen-options-link-wrap" class="hide-if-no-js screen-meta-toggle">' +
                    '<button type="button" id="show-settings-link" class="button show-settings" aria-controls="screen-options-wrap" aria-expanded="false">' +
                    wp_debug_toolkit_customizer.screen_options_text +
                    '</button>' +
                    '</div>'
                );

                // Créer le conteneur pour les options d'écran
                $('body').append(
                    '<div id="screen-options-wrap" class="hidden" tabindex="-1" aria-label="' + wp_debug_toolkit_customizer.screen_options_text + '">' +
                    '<form id="adv-settings" method="post">' +
                    '<fieldset class="metabox-prefs">' +
                    '<legend>' + wp_debug_toolkit_customizer.available_tools_text + '</legend>' +
                    '<div id="wp-debug-toolkit-available-tools"></div>' +
                    '</fieldset>' +
                    '<button type="button" class="button button-primary" id="wp-debug-toolkit-save-screen-options">' +
                    wp_debug_toolkit_customizer.apply_text +
                    '</button>' +
                    '</form>' +
                    '</div>'
                );

                // Charger la liste des outils disponibles
                this.loadAvailableTools();
            }
        },

        /**
         * Charge la liste des outils disponibles via AJAX
         */
        loadAvailableTools: function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_debug_toolkit_get_available_tools',
                    nonce: wp_debug_toolkit_customizer.nonce
                },
                success: function(response) {
                    if (response.success) {
                        WPDebugToolkitCustomizer.renderAvailableTools(response.data);
                    }
                }
            });
        },

        /**
         * Affiche la liste des outils disponibles dans les options d'écran
         */
        renderAvailableTools: function(tools) {
            var $container = $('#wp-debug-toolkit-available-tools');
            $container.empty();

            // Créer un toggle pour chaque outil
            $.each(tools, function(id, tool) {
                var isActive = tool.active;
                var $toolOption = $(
                    '<label for="wp-debug-toolkit-tool-' + id + '-hide">' +
                    '<input class="hide-postbox-tog" name="wp-debug-toolkit-tool-' + id + '-hide" ' +
                    'type="checkbox" id="wp-debug-toolkit-tool-' + id + '-hide" value="' + id + '" ' +
                    (isActive ? 'checked="checked"' : '') + '>' +
                    ' ' + tool.title +
                    '</label>'
                );
                $container.append($toolOption);
            });
        },

        /**
         * Lie les événements aux éléments
         */
        bindEvents: function() {
            // Toggle pour montrer/cacher les options d'écran
            $(document).on('click', '#show-settings-link', function(e) {
                e.preventDefault();
                $('#screen-options-wrap').toggleClass('hidden');
                var isExpanded = $(this).attr('aria-expanded') === 'true';
                $(this).attr('aria-expanded', !isExpanded);
            });

            // Clic sur les toggles d'outils pour montrer/cacher
            $(document).on('click', '.wp-debug-toolkit-tool-toggle', function(e) {
                e.preventDefault();
                var $card = $(this).closest('.wp-debug-toolkit-tool-card');
                var toolId = $card.data('tool-id');
                var isHidden = $card.hasClass('wp-debug-toolkit-tool-hidden');

                // Basculer la classe et l'icône
                $card.toggleClass('wp-debug-toolkit-tool-hidden');
                $(this).toggleClass('dashicons-visibility dashicons-hidden');

                // Mettre à jour l'attribut aria-expanded
                $(this).attr('aria-expanded', isHidden ? 'true' : 'false');

                // Sauvegarder la préférence
                WPDebugToolkitCustomizer.saveToolUserPreference(toolId, 'hidden', !isHidden);
            });

            // Enregistrement des options d'écran
            $(document).on('click', '#wp-debug-toolkit-save-screen-options', function(e) {
                e.preventDefault();
                WPDebugToolkitCustomizer.saveScreenOptions();
            });
        },

        /**
         * Enregistre l'ordre des outils
         */
        saveToolsOrder: function() {
            var toolsOrder = [];

            $('.wp-debug-toolkit-tool-card').each(function() {
                toolsOrder.push($(this).data('tool-id'));
            });

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_debug_toolkit_save_tools_order',
                    order: toolsOrder,
                    nonce: wp_debug_toolkit_customizer.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Optionnel: ajouter un feedback visuel
                    }
                }
            });
        },

        /**
         * Enregistre les options d'écran (outils actifs/inactifs)
         */
        saveScreenOptions: function() {
            var activeTools = {};

            $('.hide-postbox-tog').each(function() {
                var toolId = $(this).val();
                activeTools[toolId] = $(this).is(':checked');
            });

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_debug_toolkit_save_active_tools',
                    active_tools: activeTools,
                    nonce: wp_debug_toolkit_customizer.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Recharger la page pour afficher les changements
                        window.location.reload();
                    }
                }
            });
        },

        /**
         * Enregistre une préférence utilisateur pour un outil
         */
        saveToolUserPreference: function(toolId, prefKey, value) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_debug_toolkit_save_tool_preference',
                    tool_id: toolId,
                    pref_key: prefKey,
                    pref_value: value,
                    nonce: wp_debug_toolkit_customizer.nonce
                }
            });
        },

        /**
         * Récupère une préférence utilisateur pour un outil
         */
        getToolUserPreference: function(toolId, prefKey) {
            // Cette fonction utilise les préférences chargées côté serveur et injectées dans la page
            if (typeof wp_debug_toolkit_customizer.user_preferences !== 'undefined' &&
                typeof wp_debug_toolkit_customizer.user_preferences[toolId] !== 'undefined' &&
                typeof wp_debug_toolkit_customizer.user_preferences[toolId][prefKey] !== 'undefined') {
                return wp_debug_toolkit_customizer.user_preferences[toolId][prefKey];
            }
            return false;
        }
    };

    // Initialiser lorsque le document est prêt
    $(document).ready(function() {
        // Vérifier si nous sommes sur la page du tableau de bord du plugin
        if ($('.wp-debug-toolkit-dashboard').length) {
            WPDebugToolkitCustomizer.init();
        }
    });

})(jQuery);