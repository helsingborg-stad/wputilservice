<?php

namespace WpUtilService\Features;

use WpServiceTrait;

enum RuntimeContextEnum: string
{
    case THEME = 'theme';
    case CHILDTHEME = 'child-theme';
    case MUPLUGIN = 'mu-plugin';
    case PLUGIN = 'plugin';
}

class RuntimeContextManager
{
    use WpServiceTrait;

    /**
     * This function detects what context that we are in, and returns the following enums.
     *
     * THEME,
     * CHILDTHEME,
     * MUPLUGIN,
     * PLUGIN
     *
     * @return array<string>
     */
    private function getRuntimeContext(): array
    {
        $contexts = [];

        // Check for theme
        if (strpos(__DIR__, get_template_directory()) !== false) {
            $contexts[] = 'THEME';
        }

        // Check for child theme
        if (is_child_theme() && strpos(__DIR__, get_stylesheet_directory()) !== false) {
            $contexts[] = 'CHILDTHEME';
        }

        // Check for MU-plugin
        if (defined('WPMU_PLUGIN_DIR') && strpos(__DIR__, WPMU_PLUGIN_DIR) !== false) {
            $contexts[] = 'MUPLUGIN';
        }

        // Check for normal plugin
        if (defined('WP_PLUGIN_DIR') && strpos(__DIR__, WP_PLUGIN_DIR) !== false) {
            $contexts[] = 'PLUGIN';
        }

        return $contexts;
    }
}