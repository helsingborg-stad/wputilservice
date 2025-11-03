<?php

namespace WpUtilService\Features;

use WpUtilService\WpServiceTrait;

enum RuntimeContextEnum: string
{
    case THEME      = 'themes';
    case CHILDTHEME = 'child-theme';
    case MUPLUGIN   = 'mu-plugins';
    case PLUGIN     = 'plugins';
}

class RuntimeContextManager
{
    use WpServiceTrait;

    private ?string $rootPath = null;

    /**
     * Set the root path for context detection.
     * This can be any path within a theme or plugin to help determine context.
     * This will be used to get the root path of the theme or plugin.
     *
     * @param string $path
     * @return $this
     */
    public function setPath(string $path) : self
    {
        $this->rootPath = $path;

        return $this;
    }

    /**
     * Gets the root path of the current path provided. For example, if a child theme
     * path is provided, it will return the child theme root path.
     * If a plugin path is provided, it will return the plugin root path.
     *
     * @return string|null
     */
    public function getNormalizedRootPath(): ?string
    {
        $path = $this->rootPath;
        if (!$path) {
            throw new \RuntimeException('A path must be given.');
        }

        $contexts = $this->getContextOfPath($path);
        if (empty($contexts)) {
            throw new \RuntimeException('Could not determine context type from path: ' . $path);
        }
        $context = $contexts[0]; // Use first detected context

        switch ($context) {
            case RuntimeContextEnum::THEME:
            case RuntimeContextEnum::CHILDTHEME:
                $parts = explode('/' . RuntimeContextEnum::THEME->value . '/', $path, 2);
                if (count($parts) === 2) {
                    $themeName = explode('/', $parts[1])[0];
                    return $parts[0] . '/' . RuntimeContextEnum::THEME->value . '/' . $themeName . "/";
                }
                break;
            case RuntimeContextEnum::PLUGIN:
                $parts = explode('/' . RuntimeContextEnum::PLUGIN->value . '/', $path, 2);
                if (count($parts) === 2) {
                    $pluginName = explode('/', $parts[1])[0];
                    return $parts[0] . '/' . RuntimeContextEnum::PLUGIN->value . '/' . $pluginName . '/';
                }
                break;
            case RuntimeContextEnum::MUPLUGIN:
                $parts = explode('/' . RuntimeContextEnum::MUPLUGIN->value . '/', $path, 2);
                if (count($parts) === 2) {
                    $muPluginName = explode('/', $parts[1])[0];
                    return $parts[0] . '/' . RuntimeContextEnum::MUPLUGIN->value . '/' . $muPluginName . '/';
                }
                break;
        }
        throw new \RuntimeException('Could not extract root path for context: ' . $context->value);
    }

    /**
     * Get the context of the path provided. 
     * 
     * @param string $path
     * 
     * @return array Matching contexts. 
     */
    private function getContextOfPath(string $path): array
    {
        $contexts = [];

        if (strpos($path, '/' . RuntimeContextEnum::THEME->value . '/') !== false) {
            $contexts[] = RuntimeContextEnum::THEME;
        }
        if (strpos($path, '/' . RuntimeContextEnum::CHILDTHEME->value . '/') !== false) {
            $contexts[] = RuntimeContextEnum::CHILDTHEME;
        }
        if (strpos($path, '/' . RuntimeContextEnum::MUPLUGIN->value . '/') !== false) {
            $contexts[] = RuntimeContextEnum::MUPLUGIN;
        }
        if (strpos($path, '/' . RuntimeContextEnum::PLUGIN->value . '/') !== false) {
            $contexts[] = RuntimeContextEnum::PLUGIN;
        }
        return $contexts;
    }
}