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
         * Lie les événements aux éléments
         */
        bindEvents: function() {
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
                        // Afficher une notification de sauvegarde
                        WPDebugToolkitCustomizer.showNotification();
                    }
                }
            });
        },

        /**
         * Enregistre les options d'écran (outils actifs/inactifs)
         */
        saveScreenOptions: function() {
            var activeTools = {};

            $('input[name^="wp-debug-toolkit-tool-"]').each(function() {
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
                },
                success: function(response) {
                    if (response.success) {
                        // Afficher une notification de sauvegarde
                        WPDebugToolkitCustomizer.showNotification();
                    }
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
        },

        /**
         * Affiche une notification de sauvegarde
         */
        showNotification: function() {
            var $notification = $('.wp-debug-toolkit-save-notification');

            if ($notification.length === 0) {
                $notification = $('<div class="wp-debug-toolkit-save-notification">' +
                    wp_debug_toolkit_customizer.saved_text +
                    '</div>').appendTo('body');
            }

            $notification.addClass('show');

            setTimeout(function() {
                $notification.removeClass('show');
            }, 2000);
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