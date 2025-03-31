<?php

namespace WPDebugToolkit\Tool\ElementorBlockAnalyzer;

class ElementorElementsAnalyzer
{
    /**
     * Liste les types de widgets à exclure
     */
    private array $excludedWidgetTypes = [
        'section',
        'column',
        'inner-section',
        'inner-column',
        'form',
        'button',
    ];

    /**
     * Récupère la liste des widgets Elementor utilisés dans les posts, en excluant les widgets dans la liste $excludedWidgetTypes
     */
    public function getElementorWidgets(): array
    {
        $widgets = [];

        $args = apply_filters('cc-debug-tool/includes/Tools/ElementorElements/ElementorElementsAnalyzer/getElementorWidgets', [
            'post_type' => ['post', 'page', 'elementor_library'],
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'private'],
            'meta_query' => [
                [
                    'key' => '_elementor_data',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);

        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $post_title = get_the_title();
                $post_type = get_post_type();
                $elementor_data = get_post_meta($post_id, '_elementor_data', true);

                if ($elementor_data) {
                    $data = json_decode($elementor_data, true);
                    if (is_array($data)) {
                        $this->extractWidgets($data, $post_id, $post_title, $post_type, $widgets);
                    }
                }
            }
        }

        wp_reset_postdata();
        ksort($widgets);

        return $widgets;
    }

    /**
     * Vérifie si un widget doit être exclu
     */
    private function shouldExcludeWidget(string $widgetType): bool
    {
        // Vérifie si le type de widget est dans la liste d'exclusion
        if (in_array($widgetType, $this->excludedWidgetTypes)) {
            return true;
        }
        return false;
    }

    /**
     * Parcourt récursivement les données Elementor pour extraire les widgets
     */
    private function extractWidgets(array $elements, int $post_id, string $post_title, string $post_type, array &$widgets): void
    {
        foreach ($elements as $element) {
            if (!empty($element['widgetType'])) {
                $widget_name = $element['widgetType'];

                if ($this->shouldExcludeWidget($widget_name)) {
                    continue;
                }

                // Initialisation si le widget n'existe pas encore
                if (!isset($widgets[$widget_name])) {
                    $widgets[$widget_name] = [
                        'widget' => $widget_name,
                        'posts' => [],
                        'templates' => [],
                        'popups' => [],
                        'theme_elements' => []
                    ];
                }

                // Classement selon le type de contenu
                if ($post_type === 'elementor_library') {
                    $template_type = get_post_meta($post_id, '_elementor_template_type', true);

                    switch ($template_type) {
                        case 'header':
                        case 'footer':
                        case '404':
                        case 'single':
                        case 'archive':
                            // Ajout au lieu de remplacement
                            if (!isset($widgets[$widget_name]['theme_elements'][$post_id])) {
                                $widgets[$widget_name]['theme_elements'][$post_id] = [
                                    'title' => $post_title,
                                    'type' => $template_type
                                ];
                            }
                            break;
                        case 'popup':
                            if (!isset($widgets[$widget_name]['popups'][$post_id])) {
                                $widgets[$widget_name]['popups'][$post_id] = $post_title;
                            }
                            break;
                        default:
                            if (!isset($widgets[$widget_name]['templates'][$post_id])) {
                                $widgets[$widget_name]['templates'][$post_id] = $post_title;
                            }
                            break;
                    }
                } else {
                    if (!isset($widgets[$widget_name]['posts'][$post_id])) {
                        $widgets[$widget_name]['posts'][$post_id] = $post_title;
                    }
                }
            }

            if (!empty($element['elements'])) {
                $this->extractWidgets($element['elements'], $post_id, $post_title, $post_type, $widgets);
            }
        }
    }
}