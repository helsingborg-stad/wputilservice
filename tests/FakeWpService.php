<?php
declare(strict_types=1);

namespace WpUtilService\Tests;

use WpService\Implementations\FakeWpService as BaseFakeWpService;

class FakeWpService extends BaseFakeWpService
{
    public array $registeredScripts = [];
    public array $enqueuedScripts = [];
    public array $localizedScripts = [];
    public array $filters = [];

    public function __construct(private array $methods = []) {}

    public function __call(string $name, array $arguments)
    {
        if (isset($this->methods[$name])) {
            return ($this->methods[$name])(...$arguments);
        }

        if (method_exists($this, $name)) {
            return $this->{$name}(...$arguments);
        }

        throw new \BadMethodCallException("Method {$name} does not exist.");
    }

    public function wpRegisterScript(string $handle, string|false $src, array $deps = [], string|bool|null $ver = false, array|bool $args = []): bool
    {
        $this->registeredScripts[$handle] = compact('src', 'deps', 'ver', 'args');
        return true;
    }

    public function wpEnqueueScript(string $handle, string $src = '', array $deps = [], string|bool|null $ver = false, array|bool $args = []): void
    {
        $this->enqueuedScripts[] = $handle;
    }

    public function wpLocalizeScript(string $handle, string $objectName, array $data): bool
    {
        $this->localizedScripts[$handle][$objectName] = $data;
        return true;
    }

    public function wpRegisterStyle(string $handle, string|false $src, array $deps = [], string|bool|null $ver = false, string $media = 'all'): bool
    {
        return true;
    }

    public function wpEnqueueStyle(string $handle, string $src = '', array $deps = [], string|bool|null $ver = false, string $media = 'all'): void
    {
        // Simulate style enqueue
    }

    public function addFilter(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1): true
    {
        $this->filters[$hookName][] = compact('callback', 'priority', 'acceptedArgs');
        return true;
    }

    public function getTemplateDirectoryUri(): string
    {
        return '';
    }
}
