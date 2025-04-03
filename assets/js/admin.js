/**
 * Script principal pour WP Debug Toolkit
 */
(function($) {
    'use strict';

    // Objet principal pour les fonctionnalités du plugin
    var WPDebugToolkit = {
        /**
         * Initialisation
         */
        init: function() {
            // Initialiser les fonctionnalités du tableau de bord si nous sommes sur cette page
            if ($('.wp-debug-toolkit-dashboard').length) {
                this.initDashboard();
            }

            // Initialiser les fonctionnalités de la page paramètres si nous sommes sur cette page
            if ($('.wp-debug-toolkit-tools-switches').length) {
                this.initSettings();
            }
        },

        /**
         * Initialise les fonctionnalités du tableau de bord
         */
        initDashboard: function() {
            this.initSortable();
            this.bindDashboardEvents();
        },

        /**
         * Initialise les fonctionnalités de la page paramètres
         */
        initSettings: function() {
            this.initToolSwitches();
            this.bindSettingsEvents();
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
                    WPDebugToolkit.saveToolsOrder();
                }
            }).disableSelection();
        },

        /**
         * Initialise les switches des outils sur la page paramètres
         */
        initToolSwitches: function() {
            // Déjà géré par le script inline dans settings.php
        },

        /**
         * Lie les événements aux éléments du tableau de bord
         */
        bindDashboardEvents: function() {
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
                WPDebugToolkit.saveToolUserPreference(toolId, 'hidden', !isHidden);
            });
        },

        /**
         * Lie les événements aux éléments de la page paramètres
         */
        bindSettingsEvents: function() {
            // Les événements sont déjà gérés par le script inline dans settings.php
            // et par la soumission du formulaire
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
                        WPDebugToolkit.showNotification();
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
                        WPDebugToolkit.showNotification();
                    }
                }
            });
        },

        /**
         * Récupère une préférence utilisateur pour un outil
         */
        getToolUserPreference: function(toolId, prefKey) {
            // Cette fonction utilise les préférences chargées côté serveur et injectées dans la page
            if (typeof wp_debug_toolkit_customizer !== 'undefined' &&
                typeof wp_debug_toolkit_customizer.user_preferences !== 'undefined' &&
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
        WPDebugToolkit.init();
    });

})(jQuery);