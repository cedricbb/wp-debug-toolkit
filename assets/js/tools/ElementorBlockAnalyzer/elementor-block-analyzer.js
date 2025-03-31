jQuery(document).ready(function($) {
    // Gestion du show/hide pour les listes d'éléments
    $('.show-more-elements').on('click', function(e) {
        e.preventDefault();

        const container = $(this).closest('.elements-container');
        const hiddenElements = container.find('.hidden-element');

        // Si les éléments sont cachés, on les affiche
        if (hiddenElements.first().is(':hidden')) {
            hiddenElements.fadeIn();
            $(this).text(__('Voir moins', 'wp-debug-toolkit'));
        } else {
            // Sinon on les cache
            hiddenElements.fadeOut();
            // Recréer le texte "Voir plus" avec le bon nombre d'éléments
            const remainingCount = hiddenElements.length;
            const text = remainingCount === 1
                ? elementorWidgetsTableL10n.showMore.singular
                : elementorWidgetsTableL10n.showMore.plural;
            $(this).text(text.replace('%d', remainingCount));
        }
    });

    // Recherche en temps réel
    let searchTimeout;
    const $searchInput = $('#widget-search-input');
    const $searchForm = $searchInput.closest('form');

    function performSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            $searchForm.submit();
        }, 500);
    }

    // Événement sur l'input
    $searchInput.on('input', performSearch);

    $searchInput.on('keypress', function(e) {
        if (e.wich === 13) {
            e.preventDefault();
            clearTimeout(searchTimeout);
            $searchForm.submit();
        }
    });
});