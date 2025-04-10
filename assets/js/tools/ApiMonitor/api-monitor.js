jQuery(document).ready(function($) {

    // Gestion des filtres
    $('#apply-filters').on('click', function (e) {
        e.preventDefault();

        const endpoint = $('#endpoint-filter').val();
        const status = $('#status-filter').val();

        // Construire l'URL avec les paramètres actuels
        const currentUrl = new URL(window.location.href);

        // Mettre à jour ou supprimer les paramètres selon leur valeur
        if (endpoint) {
            currentUrl.searchParams.set('endpoint', endpoint);
        } else {
            currentUrl.searchParams.delete('endpoint');
        }

        if (status) {
            currentUrl.searchParams.set('status', status);
        } else {
            currentUrl.searchParams.delete('status');
        }

        // Préserver la vue active
        const view = currentUrl.searchParams.get('view');
        if (view) {
            currentUrl.searchParams.set('view', view);
        }

        // Rediriger vers l'URL filtrée
        window.location.href = currentUrl.toString();
    });

    // permettre l'utilisaton de la touche Enter dans les filtres
    $('.wp-debug-toolkit-api-filters select').on('keypress', function (e) {
        if (e.key === 13) {
            $('#apply-filters').click();
        }
    });

    // Gestion du bouton Actualiser
    $('#refresh-data').on('click', function(e) {
        e.preventDefault();
        // Ajouter une classe pour l'animation de rotation
        $(this).addClass('rotating');
        // Recharger la page en préservant les filtres actuels
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('refresh', Date.now());
        window.location.href = currentUrl.toString();
    })

    // Gestion du bouton Effacer les logs
    $('#clear-logs').on('click', function(e) {
        e.preventDefault();
        console.log('Tentative de suppression des logs');

        if (!confirm(wpDebugToolkitApiMonitor.strings.confirmClear)) {
            return;
        }

        const $button = $(this);
        $button.prop('disabled', true).addClass('updating-message');

        // Requête AJAX pour supprimer les logs
        $.ajax({
            url: wpDebugToolkitApiMonitor.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'clear_api_logs',
                _wpnonce: wpDebugToolkitApiMonitor.nonce
            },
            success: function(response) {
                console.log('Réponse de clear_logs:', response);

                if (response.success) {
                    // Afficher le message de succès
                    const $notice = $('<div class="notice notice-success is-dismissible"><p>' +
                        (response.data.message || wpDebugToolkitApiMonitor.strings.clearSuccess) +
                        '</p></div>');

                    $('.wp-debug-toolkit-api-toolbar').after($notice);

                    // Recharger la page après un court délai
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Afficher le message d'erreur
                    const errorMessage = response.data ? response.data.message : wpDebugToolkitApiMonitor.strings.clearError;
                    const $notice = $('<div class="notice notice-error is-dismissible"><p>' +
                        errorMessage +
                        '</p></div>');

                    $('.wp-debug-toolkit-api-toolbar').after($notice);
                    $button.prop('disabled', false).removeClass('updating-message');
                }
            },
            error: function(xhr, status, errorThrown) {
                console.error('Erreur lors de la suppression des logs:', {
                    status: status,
                    error: errorThrown,
                    xhr: xhr
                });

                // Afficher le message d'erreur
                const $notice = $('<div class="notice notice-error is-dismissible"><p>' +
                    wpDebugToolkitApiMonitor.strings.clearError +
                    '</p></div>');

                $('.wp-debug-toolkit-api-toolbar').after($notice);
                $button.prop('disabled', false).removeClass('updating-message');
            }
        });
    });

    // Fonction pour formater le JSON
    function formatJSONString(str) {
        try {
            // Si c'est déjà un objet, on le convertit en string
            if (typeof str === 'object') {
                return JSON.stringify(str, null, 4);
            }
            // Si c'est une string JSON, on la parse puis on la reformate
            const obj = JSON.parse(str);
            return JSON.stringify(obj, null, 4);
        } catch (e) {
            // Si ce n'est pas du JSON valide, on retourne la string telle quelle
            return str;
        }
    }

    // Fonction d'initialisation des onglets
    function initializeTabs() {

        const $modal = $('.wp-debug-toolkit-api-call-details');

        // S'assurer que l'onglet "request" est actif par défaut
        $modal.find('.tab-panel').removeClass('active');
        $modal.find('#request-panel').addClass('active');

        $modal.find('.details-tabs .tab-button').removeClass('active');
        $modal.find('.details-tabs .tab-button[data-tab="request"]').addClass('active');

        // Gestionnaire de clic pour les onglets dans la modal uniquement
        $modal.find('.details-tabs .tab-button').off('click').on('click', function() {
            const $button = $(this);
            const tabId = $button.data('tab');

            // Activer l'onglet en restant dans le contexte de la modal
            $modal.find('.details-tabs .tab-button').removeClass('active');
            $button.addClass('active');

            // Afficher le contenu correspondant
            $modal.find('.tab-panel').removeClass('active');
            $modal.find('#' + tabId + '-panel').addClass('active');
        });

        // Initialiser les boutons de copie
        $modal.find('.copy-button').off('click').on('click', function() {
            const $button = $(this);
            let text = $button.data('clipboard-text');
            
            // Si le bouton est dans le panneau de réponse, on formate le JSON
            if ($button.closest('#response').length > 0) {
                text = formatJSONString(text);
            }
            
            navigator.clipboard.writeText(text).then(function() {
                $button.addClass('copied');
                setTimeout(() => {
                    $button.removeClass('copied');
                }, 1000);
            });
        });
    }

    // Gestionnaire de clic pour le bouton de détails
    $(document).on('click', '.view-details', function(e) {
        e.preventDefault();

        const $button = $(this);
        const logId = $button.data('log-id');

        if (!logId) {
            console.error('Pas de log_id trouvé sur le bouton');
            return;
        }

        // Ouvrir ThickBox
        tb_show(
            wpDebugToolkitApiMonitor.strings.modalTitle,
            '#TB_inline?width=800&height=600&inlineId=api-call-details'
        );

        // Afficher le message de chargement
        $('#api-call-details-content').html(
            '<div class="loading-spinner">' + wpDebugToolkitApiMonitor.strings.loading + '</div>'
        );

        // Requête AJAX
        $.ajax({
            url: wpDebugToolkitApiMonitor.ajaxurl,
            type: 'GET',
            data: {
                action: 'get_api_details',
                log_id: logId,
                _wpnonce: wpDebugToolkitApiMonitor.nonce
            },
            success: function(response) {

                if (response.success && response.data) {
                    $('#api-call-details-content').html(response.data.content);

                    // Initialiser les onglets après avoir ajouté le contenu
                    setTimeout(initializeTabs, 100);

                    $('#TB_window').addClass('api-call-details-modal');
                    if (typeof tb_position === 'function') {
                        tb_position();
                    }
                } else {
                    $('#api-call-details-content').html(
                        '<div class="notice notice-error"><p>' +
                        (response.data?.message || wpDebugToolkitApiMonitor.strings.error) +
                        '</p></div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', {
                    status: status,
                    error: error
                });
                $('#api-call-details-content').html(
                    '<div class="notice notice-error"><p>' +
                    wpDebugToolkitApiMonitor.strings.error +
                    '</p></div>'
                );
            }
        });
    });

    // Nettoyage lors de la fermeture
    $(window).on('tb_unload', function() {
        $('#TB_window').removeClass('api-call-details-modal');
        $('#api-call-details-content').empty();
    });
});