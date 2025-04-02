
jQuery(document).ready(function($) {
    let currentChart = null;

    // Initialisation des postboxes
    function initializePostbox() {
        // Initialiser les postboxes de WordPress
        $('.postbox').each(function() {
            const $postbox = $(this);

            // Toggle sur le clic du bouton
            $postbox.find('.handlediv').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                $postbox.toggleClass('closed');

                // Mettre à jour l'attribut aria-expanded
                const isExpanded = !$postbox.hasClass('closed');
                $(this).attr('aria-expanded', isExpanded);

                // Si on ouvre la postbox et qu'il y a un graphique, le redimensionner
                if (isExpanded && currentChart) {
                    setTimeout(() => {
                        currentChart.resize();
                    }, 10);
                }
            });

            // Toggle sur le clic du titre
            $postbox.find('.hndle').on('click', function(e) {
                e.preventDefault();
                $postbox.find('.handlediv').click();
            });
        });
    }

    function initializeUsageChart(chartData) {
        const ctx = document.getElementById('usageChart');

        if (!ctx) {
            console.error('Canvas element not found');
            return;
        }

        if (currentChart) {
            currentChart.destroy();
        }

        currentChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Utilisations',
                    data: chartData.values,
                    borderColor: 'rgb(239, 71, 111)',
                    backgroundColor: 'rgba(239, 71, 111, .1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Mise à jour du graphique lorsque la postbox est ouverte
    $('#usage_history').on('postbox-toggled', function() {
        if (currentChart && !$(this).hasClass('closed')) {
            currentChart.resize();
        }
    });

    $(document).on('click', '.widget-details-link', function(e) {
        e.preventDefault();

        const $link = $(this);
        const widgetName = $link.data('widget');

        // Ouvrir ThickBox avec un message de chargement
        tb_show(
            WPDebugWidgetVars.strings.modalTitle,
            '#TB_inline?width=800&height=600&inlineId=TB_ajaxContent'
        );

        // Afficher le message de chargement
        $('#TB_ajaxContent').html(WPDebugWidgetVars.strings.loading);

        // Requête AJAX
        $.ajax({
            url: WPDebugWidgetVars.ajaxurl,
            type: 'GET',
            data: {
                action: 'get_widget_details',
                widget: widgetName,
                _wpnonce: WPDebugWidgetVars.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Mettre à jour le contenu de la modale
                    $('#TB_ajaxContent').html(response.data.content);

                    // Initialiser le graphique après le chargement du contenu
                    $('#api-call-details-content').html(response.data.content);

                    // Initialiser la postbox après avoir chargé le contenu
                    initializePostbox();

                    if (response.data.chartData) {
                        initializeUsageChart(response.data.chartData);
                    }

                    $('#TB_window').addClass('api-call-details-modal');
                    if (typeof tb_position === 'function') {
                        tb_position();
                    }
                } else {
                    $('#TB_ajaxContent').html(
                        '<div class="notice notice-error"><p>' +
                        WPDebugWidgetVars.strings.error +
                        '</p></div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                $('#TB_ajaxContent').html(
                    '<div class="notice notice-error"><p>' +
                    WPDebugWidgetVars.strings.error +
                    '</p></div>'
                );
            }
        });
    });

    // Nettoyage lors de la fermeture
    $(window).on('tb_unload', function() {
        if (currentChart) {
            currentChart.destroy();
            currentChart = null;
        }
    });
});
