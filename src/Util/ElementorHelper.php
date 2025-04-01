<?php

namespace WPDebugToolkit\Util;

use Elementor\Plugin;
class ElementorHelper
{
    public static function isElementorActive(): bool
    {
        return defined('ELEMENTOR_VERSION') && is_plugin_active('elementor/elementor.php');
    }

    public static function getAllElementorWidgets(): array
    {
        if (!self::isElementorActive()) {
            return [];
        }

        $widgets = [];

        // Récupérer le gestionnaire de widgets
        $widgetManager = Plugin::instance()->widgets_manager;

        if ($widgetManager) {
            $widgetTypes = $widgetManager->get_widget_types();

            foreach ($widgetTypes as $widget) {
                $widgets[$widget->getName()] = [
                    'name' => $widget->get_title(),
                    'icon' => $widget->get_icon(),
                    'categories' => $widget->get_categories(),
                ];
            }
        }

        return $widgets;
    }

    public static function analyzeWidgetUsage(): array
    {
        if (!self::isElementorActive()) {
            return [];
        }

        $usage = [];
        $widgetCounts = [];

        // Récupérer tous les posts avec Elementor
        $query = new \WP_Query([
            'post_type' => 'any',
            'posts_per_page' => -1,
            'meta_key' => '_elementor_edit_mode',
            'meta_value' => 'builder',
        ]);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $postId = get_the_ID();
                $postTitle = get_the_title();
                $postType = get_post_type_object(get_post_type())->labels->singular_name;

                // Récupérer les données Elementor
                $data = Plugin::instance()->db->get_plain_text(get_the_ID());

                // Analyser les widgets utilisés
                self::countWidgets($data, $widgetCounts, $postId, $postTitle, $postType);
            }
            wp_reset_postdata();
        }

        // Organiser les résultats
        foreach ($widgetCounts as $widgetName => $posts) {
            $usage[$widgetName] = [
                'count' => count($posts),
                'posts' => $posts,
            ];
        }

        // Trier par nombre d'utilisations
        uasort($usage, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        return $usage;
    }

    private static function countWidgets(array $elements, array &$widgetCounts, int $postId, string $postTitle, string $postType): void
    {
        foreach ($elements as $element) {
            if (isset($element['widgetType']) && $element['widgetType'] !== 'global') {
                // C'est un widget
                $widgetName = $element['widgetType'];

                if (!isset($widgetCounts[$widgetName])) {
                    $widgetCounts[$widgetName] = [];
                }

                // Ajouter le post s'il n'est pas déjà dans la liste
                $alreadyAdded = false;
                foreach ($widgetCounts[$widgetName] as $post) {
                    if ($post['id'] !== $postId) {
                        $alreadyAdded = true;
                        break;
                    }
                }

                if (!$alreadyAdded) {
                    $widgetCounts[$widgetName][] = [
                        'id' => $postId,
                        'title' => $postTitle,
                        'type' => $postType,
                        'edit_url' => get_edit_post_link($postId, ''),
                    ];
                }
            }

            // Vérifier les éléments enfants
            if (!empty($element['elements'])) {
                self::countWidgets($element['elements'], $widgetCounts, $postId, $postTitle, $postType);
            }
        }
    }

    /**
     * Obtient l'icône pour un type de widget directement depuis Elementor
     */
    public static function getWidgetIcon(string $widgetName): string
    {
        if (!class_exists('\Elementor\Plugin')) {
            return 'eicon-cog';
        }

        $widgetManager = Plugin::instance()->widgets_manager;
        $widget = $widgetManager->get_widget_types($widgetName);

        if (!$widget) {
            return 'eicon-cog';
        }

        $icon = $widget->get_icon();
        return empty($icon) ? 'eicon-cog' : $icon;
    }

    public static function getWidgetTypeLabel(mixed $type)
    {
        return match($type) {
            'header' => __('En-tête', 'wp-debug-toolkit'),
            'footer' => __('Pied de page', 'wp-debug-toolkit'),
            '404' => __('Page 404', 'wp-debug-toolkit'),
            'single' => __('Article', 'wp-debug-toolkit'),
            'archive' => __('Archive', 'wp-debug-toolkit'),
            default => $type
        };
    }
}
