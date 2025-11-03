<?php

namespace WpUtilService\Features;

use WpUtilService\WpServiceTrait;

enum RuntimeContextEnum: string
{
    case THEME      = 'themes';
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

        $context = $this->getContextOfPath($path);
        if ($context === null) {
            throw new \RuntimeException('Could not determine context type from path: ' . $path);
        }

        $parts = explode('/' . $context->value . '/', $path, 2);
        if (count($parts) === 2) {
            $name = explode('/', $parts[1])[0];
            return $parts[0] . '/' . $context->value . '/' . $name . '/';
        }
        throw new \RuntimeException('Could not extract root path for context: ' . $context->value);
    }

    /**
    * Get the context of the path provided.
    *
    * @param string $path
    * @return RuntimeContextEnum|null Matching context or null if not found.
    */
    private function getContextOfPath(string $path): ?RuntimeContextEnum
    {
        if (strpos($path, '/' . RuntimeContextEnum::THEME->value . '/') !== false) {
            return RuntimeContextEnum::THEME;
        }
        if (strpos($path, '/' . RuntimeContextEnum::MUPLUGIN->value . '/') !== false) {
            return RuntimeContextEnum::MUPLUGIN;
        }
        if (strpos($path, '/' . RuntimeContextEnum::PLUGIN->value . '/') !== false) {
            return RuntimeContextEnum::PLUGIN;
        }
        return null;
    }
}