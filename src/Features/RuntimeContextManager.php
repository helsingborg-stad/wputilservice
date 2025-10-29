<?php

namespace WpUtilService\Features;

use WpUtilService\WpServiceTrait;

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
     * Detects the runtime context and returns the corresponding enum.
     *
     * @return RuntimeContextEnum|null
     */
    private function getRuntimeContext(): ?RuntimeContextEnum
    {
        // Check for child theme first (more specific than theme)
        if (is_child_theme() && strpos(__DIR__, get_stylesheet_directory()) !== false) {
            return RuntimeContextEnum::CHILDTHEME;
        }

        // Check for theme
        if (strpos(__DIR__, get_template_directory()) !== false) {
            return RuntimeContextEnum::THEME;
        }

        // Check for MU-plugin
        if (defined('WPMU_PLUGIN_DIR') && strpos(__DIR__, WPMU_PLUGIN_DIR) !== false) {
            return RuntimeContextEnum::MUPLUGIN;
        }

        // Check for normal plugin
        if (defined('WP_PLUGIN_DIR') && strpos(__DIR__, WP_PLUGIN_DIR) !== false) {
            return RuntimeContextEnum::PLUGIN;
        }

        // Nothing matched
        return null;
    }
}