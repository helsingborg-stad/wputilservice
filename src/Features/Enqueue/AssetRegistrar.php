<?php

declare(strict_types=1);

namespace WpUtilService\Features\Enqueue;

use WpService\WpService;

/**
 * Registers and enqueues CSS and JS assets.
 */
class AssetRegistrar
{
    /**
     * Constructor.
     *
     * @param WpService $wpService
     */
    public function __construct(
        private WpService $wpService
    ) {
    }

    /**
     * Get the register/enqueue/localize callables for a type.
     *
     * @param string $type 'js'|'css'
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function getRegisterEnqueueFunctions(string $type): array
    {
        if ($type === 'js') {
            return [
                'register' => fn($handle, $src, $deps) =>
                    $this->wpService->wpRegisterScript($handle, $src, $deps, false, true),
                'enqueue'  => fn($handle) =>
                    $this->wpService->wpEnqueueScript($handle),
                'localize' => fn($handle, $objectName, $data) =>
                    $this->wpService->wpLocalizeScript($handle, $objectName, $data),
                'data'    => fn($handle, $objectName, $data) => 
                    $this->wpService->wpAddInlineScript(
                        $handle,
                        'var ' . $objectName . ' = ' . wp_json_encode($data) . ';',
                        'before'
                    )
            ];
        }

        if ($type === 'css') {
            return [
                'register' => fn($handle, $src, $deps) =>
                    $this->wpService->wpRegisterStyle($handle, $src, $deps, false),
                'enqueue'  => fn($handle) =>
                    $this->wpService->wpEnqueueStyle($handle),
            ];
        }

        throw new \InvalidArgumentException('Invalid type provided. Use "js" or "css".');
    }

    /**
     * Get the file type from the source string.
     *
     * @param string $src
     * @param string $handle
     * @return string 'js'|'css'
     *
     * @throws \InvalidArgumentException
     */
    public function getFileType(string $src, string $handle = ''): string
    {
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION) ?? '');

        if (empty($ext)) {
            throw new \InvalidArgumentException(
                "Could not determine file extension from source: {$src} using handle: {$handle}"
            );
        }

        if (!in_array($ext, ['js', 'css'], true)) {
            throw new \InvalidArgumentException("Unsupported file extension: {$ext}");
        }

        return $ext;
    }

    /**
     * Infer the asset type for a given handle.
     *
     * @param string $handle
     * @return string 'js'|'css'
     */
    public function getAssetTypeForHandle(string $handle): string
    {
        // Directly check the suffix of the handle
        if (str_ends_with($handle, 'Css')) {
            return 'css';
        }
        if (str_ends_with($handle, 'Js')) {
            return 'js';
        }

        // Throw an exception if the type cannot be determined
        throw new \InvalidArgumentException("Cannot determine asset type for handle: {$handle}");
    }
}
