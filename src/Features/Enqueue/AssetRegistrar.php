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
     * Accumulate asset operations to be executed when the hook fires.
     */
    private array $pendingAssetOps = [];

    /**
     * Flag to ensure the hook is only registered once.
     */
    private bool $hookRegistered = false;
    /**
     * @var enqueueHook The hook on which to enqueue assets.
     */
    private null|string $enqueueHook = null;

    /**
     * @var int $enqueuePriority The priority for the enqueue hook.
     */
    private int $enqueuePriority = 10;

    /**
     * Constructor.
     *
     * @param WpService $wpService
     */
    public function __construct(
        private WpService $wpService,
    ) {}

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
        // Wrap functions to optionally hook into WordPress actions
        $wrapWithAction = function (callable $fn) {
            if ($this->enqueueHook !== null) {
                return function (...$args) use ($fn) {
                    $this->pendingAssetOps[] = function () use ($fn, $args) {
                        $fn(...$args);
                    };
                    if (!$this->hookRegistered) {
                        $this->wpService->addAction(
                            $this->enqueueHook,
                            function () {
                                foreach ($this->pendingAssetOps as $op) {
                                    $op();
                                }
                                $this->pendingAssetOps = [];
                            },
                            $this->enqueuePriority,
                        );
                        $this->hookRegistered = true;
                    }
                };
            }
            return $fn;
        };

        if ($type === 'js') {
            return [
                'register' => $wrapWithAction(
                    fn($handle, $src, $deps) => $this->wpService->wpRegisterScript($handle, $src, $deps, false, true),
                ),
                'enqueue' => $wrapWithAction(fn($handle) => $this->wpService->wpEnqueueScript($handle)),
                'localize' => $wrapWithAction(
                    fn($handle, $objectName, $data) => $this->wpService->wpLocalizeScript($handle, $objectName, $data),
                ),
                'data' => $wrapWithAction(fn($handle, $objectName, $data) => $this->wpService->wpAddInlineScript(
                    $handle,
                    'var ' . $objectName . ' = ' . $this->wpService->wpJsonEncode($data) . ';',
                    'before',
                )),
            ];
        }

        if ($type === 'css') {
            return [
                'register' => $wrapWithAction(
                    fn($handle, $src, $deps) => $this->wpService->wpRegisterStyle($handle, $src, $deps, false),
                ),
                'enqueue' => $wrapWithAction(fn($handle) => $this->wpService->wpEnqueueStyle($handle)),
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
                "Could not determine file extension from source: {$src} using handle: {$handle}",
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
        if (str_ends_with($handle, 'css')) {
            return 'css';
        }
        if (str_ends_with($handle, 'js')) {
            return 'js';
        }

        // Throw an exception if the type cannot be determined
        throw new \InvalidArgumentException("Cannot determine asset type for handle: {$handle}");
    }

    /**
     * Set the hook on which to enqueue assets.
     *
     * @param string $hook
     * @param int $priority The priority for the hook (default: 10)
     *
     * @throws \InvalidArgumentException
     */
    public function setEnqueueHook(string $hook, int $priority = 10): void
    {
        $allowed = [
            'wp_enqueue_scripts',
            'admin_enqueue_scripts',
            'login_enqueue_scripts',
            'enqueue_block_assets',
            'enqueue_block_editor_assets',
        ];

        $isAllowed =
            in_array($hook, $allowed, true)
            || str_starts_with($hook, 'admin_print_scripts-')
            || str_starts_with($hook, 'admin_print_styles-');

        if (!$isAllowed) {
            throw new \InvalidArgumentException("Invalid enqueue hook: {($hook ?: 'EMPTY_HOOK_NAME')}");
        }

        $this->enqueueHook = $hook;
        $this->enqueuePriority = $priority;
    }
}
