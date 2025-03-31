<?php

namespace WPDebugToolkit\Tool\ElementorBlockAnalyzer;

use WP_List_Table;
use WPDebugToolkit\Util\ElementorHelper;

final class ElementorWidgetsTable extends WP_List_Table
{
    private array $widgets = [];
    private array $filteredData = [];
    private string $searchQuery = '';

    /**
     * Constructeur
     */
    public function __construct(array$widgets) {
        parent::__construct([
            'singular' => 'widget',
            'plural' => 'widgets',
            'ajax' => false,
        ]);
        $this->widgets = $widgets;
        // Initialiser les données filtrées avec les données brutes
        $this->filteredData = $this->prepareFilteredData();

        wp_localize_script(
            'cc-elementor-table',
            'elementorWidgetsTableL10n',
            [
                'showMore' => [
                    'singular' => __('Voir %d élément de plus', 'wp-debug-toolkit'),
                    'plural' => __('Voir %d éléments de plus', 'wp-debug-toolkit')
                ],
                'showLess' => __('Voir moins', 'wp-debug-toolkit')
            ]
        );
    }

    /**
     * Obtient l'icône pour un type d'élément de thème
     */
    private function get_element_type_icon(string $type): string
    {
        return match ($type) {
            'header' => 'dashicons-welcome-widgets-menus',
            'footer' => 'dashicons-arrow-down-alt',
            '404' => 'dashicons-warning',
            'single' => 'dashicons-admin-post',
            'archive' => 'dashicons-archive',
            default => 'dashicons-admin-generic'
        };
    }

    /**
     * Obtient le label pour un type d'élément de thème
     */
    private function get_element_type_label(string $type): string
    {
        return match ($type) {
            'header' => 'En-tête',
            'footer' => 'Pied de page',
            '404' => 'Page 404',
            'single' => 'Article',
            'archive' => 'Archive',
            default => $type
        };
    }

    /**
     * Prépare les données filtrées en fonction de la recherche
     */
    private function prepareFilteredData(): array
    {
        $this->searchQuery = $_REQUEST['s'] ?? '';
        $data = [];

        foreach ($this->widgets as $widgetName => $widgetData) {
            if ($this->matchesSearch($widgetName, $widgetData)) {
                $data[] = [
                    'widget' => $widgetName,
                    'usage' => [
                        'posts' => $widgetData['posts'] ?? [],
                        'templates' => $widgetData['templates'] ?? [],
                        'popups' => $widgetData['popups'] ?? [],
                        'theme_elements' => $widgetData['theme_elements'] ?? []
                    ],
                    'total_uses' => $this->calculateTotalUses($widgetData)
                ];
            }
        }
        return $data;
    }

    /**
     * Vérifie si le widget correspond à la recherche
     */
    private function matchesSearch(string $widgetName, array $widgetData): bool
    {
        if (empty($this->searchQuery)) {
            return true;
        }

        $searchTerm = strtolower($this->searchQuery);

        // Recherche dans le nom du widget
        if (str_contains(strtolower($widgetName), $searchTerm)) {
            return true;
        }
        // Recherche dans les titres des posts/pages
        foreach (['posts, templates, popups'] as $type) {
            if (!empty($widgetData[$type])) {
                foreach ($widgetData[$type] as $title) {
                    if (str_contains(strtolower($title), $searchTerm)) {
                        return true;
                    }
                }
            }
        }
        // Recherche dans les éléments de thème
        if (!empty($widgetData['theme_elements'])) {
            foreach ($widgetData['theme_elements'] as $element) {
                if (str_contains(strtolower($element['title'] ?? ''), $searchTerm)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Calcule le nombre total d'utilisations d'un widget
     */
    private function calculateTotalUses(array $widgetData): int
    {
        return count($widgetData['posts'] ?? []) +
            count($widgetData['templates'] ?? []) +
            count($widgetData['popups'] ?? []) +
            count($widgetData['theme_elements'] ?? []);
    }

    /**
     * Obtient l'icône pour un type de widget directement depuis Elementor
     */
    private function get_widget_icon(string $widget_name): string
    {
        if (!class_exists('\Elementor\Plugin')) {
            return 'eicon-cog';
        }
        $widget_manager = \Elementor\Plugin::instance()->widgets_manager;
        $widget = $widget_manager->get_widget_types($widget_name);

        if (!$widget) {
            return 'eicon-cog';
        }

        $icon = $widget->get_icon();
        // Si l'icône est vide
        if (empty($icon)) {
            return 'eicon-cog';
        }
        return $icon;
    }

    /**
     * Prépare les éléments à afficher
     */
    public function prepare_items(): void
    {
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        // Tri des données
        $orderby = $_REQUEST['orderby'] ?? 'widget';
        $order = $_REQUEST['order'] ?? 'asc';

        usort($this->filteredData, function ($a, $b) use ($orderby, $order) {
            $result = match ($orderby) {
                'total_uses' => $a['total_uses'] <=> $b['total_uses'],
                default => strcasecmp($a['widget'], $b['widget'])
            };
            return ($order === 'asc') ? $result : -$result;
        });

        // Pagination
        $per_page = 10;
        $current_page = $this->get_pagenum();
        $total_items = count($this->filteredData);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);

        $this->items = array_slice($this->filteredData, (($current_page - 1) * $per_page), $per_page);
    }

    /**
     * Ajoute le formulaire de recherche
     */
    public function extra_tablenav($which): void
    {
        if ($which !== 'top') {
            return;
        }
        ?>
        <div class="alignleft actions">
            <input type="search"
                   id="widget-search-input"
                   name="s"
                   value="<?php echo esc_attr($this->searchQuery); ?>"
                   placeholder="<?php esc_attr_e('Rechercher un widget...', 'wp-debug-toolkit'); ?>"/>
            <?php submit_button(__('Rechercher', 'wp-debug-toolkit'), '', 'submit', false); ?>
        </div>
        <?php
    }

    /**
     * Vérifie si un post est un template Elementor et retourne son type
     */
    private function get_elementor_template_type(int $post_id): ?string {
        if (get_post_type($post_id) !== 'elementor_library') {
            return null;
        }

        $template_type = get_post_meta($post_id, '_elementor_template_type', true);

        return $template_type ?: null;
    }

    /**
     * Définit les colonnes du tableau
     */
    public function get_columns(): array
    {
        return [
            'cb' => '<input type="checkbox" />',
            'widget' => __('Widget', 'wp-debug-toolkit'),
            'posts' => __('Pages/Posts', 'wp-debug-toolkit'),
            'templates' => __('Templates', 'wp-debug-toolkit'),
            'popups' => __('Popups', 'wp-debug-toolkit'),
            'theme_elements' => __('Éléments de thème', 'wp-debug-toolkit'),
            'total_uses' => __('Total', 'wp-debug-toolkit'),
        ];
    }

    /**
     * Définit les colonnes triables
     */
    public function get_sortable_columns(): array
    {
        return [
            'widget' => ['widget', false],
            'total_uses' => ['total_uses', false],
        ];
    }

    /**
     * Gère l'affichage de la colonne des widgets
     */
    public function column_widget($item): string
    {
        $icon = ElementorHelper::getWidgetIcon($item['widget']);

        $actions = [
            'view' => sprintf(
                '<a href="#" class="widget-details-link" data-widget="%s">%s</a>',
                esc_attr($item['widget']),
                __('Voir les détails', 'wp-debug-toolkit')
            )
        ];

        return sprintf(
            '<div class="widget-name-container">
                <i class="%s"></i>
                <span class="widget-name">%s</span>
            </div>
            %s',
            esc_attr($icon),
            esc_html($item['widget']),
            $this->row_actions($actions)
        );
    }

    /**
     * Génère le contenu de la colonne des posts/pages
     */
    private function render_posts_list(?array $posts): string
    {
        if (empty($posts)) {
            return '<span class="empty-list">-</span>';
        }

        $count = count($posts);
        $initial_display = 3;

        $output = '<div class="elements-container">';
        $output .= '<ul class="cc-widget-pages-list">';

        $i = 0;
        foreach ($posts as $post_id => $post_title) {
            $hidden_class = ($i >= $initial_display) ? 'hidden-element' : '';
            $edit_url = admin_url(sprintf('post.php?post=%d&action=elementor', $post_id));

            $output .= sprintf(
                '<li class="%s"><a href="%s" target="_blank"><span class="dashicons dashicons-admin-page"></span>%s</a></li>',
                esc_attr($hidden_class),
                esc_url($edit_url),
                esc_html($post_title)
            );
            $i++;
        }

        $output .= '</ul>';

        if ($count > $initial_display) {
            $remaining = $count - $initial_display;
            $output .= sprintf(
                '<a class="show-more-elements" href="#">%s</a>',
                sprintf(
                    _n(
                        'Voir %d élément de plus',
                        'Voir %d éléments de plus',
                        $remaining,
                        'wp-debug-toolkit'
                    ),
                    $remaining
                )
            );
        }

        $output .= '</div>';
        return $output;
    }

    /**
     * Génère le contenu de la colonne des élements de thème
     */
    public function render_theme_elements_list(?array $elements): string
    {
        if (empty($elements)) {
            return '<span class="empty-list">-</span>';
        }

        $initial_display = 3;
        $count = count($elements);

        $output = '<div class="elements-container">';
        $output .= '<ul class="cc-widget-theme-elements-list">';

        $i = 0;
        foreach ($elements as $element_id => $element) {
            $type = $element['type'] ?? 'unknown';
            $title = $element['title'] ?? 'Sans titre';
            $hidden_class = ($i >= $initial_display) ? 'hidden-element' : '';

            $type_label = match($type) {
                'header' => 'En-tête',
                'footer' => 'Pied de page',
                '404' => 'Page 404',
                'single' => 'Article',
                'archive' => 'Archive',
                default => $type
            };

            $type_icon = match($type) {
                'header' => 'dashicons-welcome-widgets-menus',
                'footer' => 'dashicons-arrow-down-alt',
                '404' => 'dashicons-warning',
                'single' => 'dashicons-admin-post',
                'archive' => 'dashicons-archive',
                default => 'dashicons-admin-generic'
            };

            $edit_url = admin_url(sprintf('post.php?post=%d&action=elementor', $element_id));

            $output .= sprintf(
                '<li class="theme-element-item theme-element-%s %s">
                <a href="%s" target="_blank">
                    <span class="dashicons %s"></span>
                    <span class="element-title">%s</span>
                    <span class="element-type">(%s)</span>
                </a>
            </li>',
                esc_attr($type),
                esc_attr($hidden_class),
                esc_url($edit_url),
                esc_attr($type_icon),
                esc_html($title),
                esc_html($type_label)
            );

            $i++;
        }

        $output .= '</ul>';

        if ($count > $initial_display) {
            $remaining = $count - $initial_display;
            $output .= sprintf(
                '<a class="show-more-elements" href="#">%s</a>',
                sprintf(
                    _n(
                        'Voir %d élément de plus',
                        'Voir %d éléments de plus',
                        $remaining,
                        'wp-debug-toolkit'
                    ),
                    $remaining
                )
            );
        }

        $output .= '</div>';
        return $output;
    }

    /**
     * Gère l'affichage de la colonne des cases à cocher
     */
    public function column_cb($item): string
    {
        return sprintf(
            '<input type="checkbox" name="widgets[]" value="%s" />',
            $item['widget']
        );
    }

    /**
     * Gère l'affichage par défaut des colonnes
     */
    public function column_default($item, $column_name): string
    {
        if (!isset($item['usage'])) {
            return '<span class="empty-list"></span>';
        }
        return match ($column_name) {
            'posts' => $this->render_posts_list($item['usage']['posts'] ?? []),
            'templates' => $this->render_posts_list($item['usage']['templates'] ?? []),
            'popups' => $this->render_posts_list($item['usage']['popups'] ?? []),
            'theme_elements' => $this->render_theme_elements_list($item['usage']['theme_elements'] ?? []),
            'total_uses' => $this->render_total_count($item['total_uses'] ?? 0),
            default => print_r($item, true)
        };
    }

    /**
     * Rendu du compteur total avec la classe CSS appropriée
     */
    private function render_total_count(int $count): string {
        $class = match(true) {
            $count > 10 => 'high-usage',
            $count > 5  => 'medium-usage',
            default     => 'low-usage'
        };

        return sprintf(
            '<strong class="total-count %s">%d</strong>',
            esc_attr($class),
            $count
        );
    }

    /**
     * Message à afficher quand il n'y a pas de données
     */
    public function no_items(): void
    {
        esc_html_e('Aucun widget Elementor trouvé.', 'wp-debug-toolkit');
    }

    /**
     * Actions groupées disponibles
     */
    public function get_bulk_actions(): array
    {
        return [
            parent::get_bulk_actions()
        ];
    }
}