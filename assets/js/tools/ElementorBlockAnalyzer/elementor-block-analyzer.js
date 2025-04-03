jQuery(document).ready(function($) {
    // Variables pour les traductions
    const showLessText = 'Voir moins';

    // Gestion du show/hide pour les listes d'éléments
    $('.show-more-elements').on('click', function(e) {
        e.preventDefault();

        const container = $(this).closest('.elements-container');
        const hiddenElements = container.find('.hidden-element');
        const count = parseInt($(this).data('count'), 10);
        const singularTemplate = $(this).data('singular');
        const pluralTemplate = $(this).data('plural');

        // Si les éléments sont cachés, on les affiche
        if (hiddenElements.first().is(':hidden')) {
            hiddenElements.show();
            $(this).text(showLessText);
        } else {
            // Sinon on les cache
            hiddenElements.hide();

            // Déterminer le texte correct
            let newText;
            if (count === 1) {
                newText = singularTemplate;
            } else {
                newText = pluralTemplate.replace('%d', count);
            }

            $(this).text(newText);
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
        if (e.which === 13) {
            e.preventDefault();
            clearTimeout(searchTimeout);
            $searchForm.submit();
        }
    });
});