<?php 

namespace WpUtilService\Tests;

use WpService\WpService;
use WpService\Implementations\FakeWpService as BaseFakeWpService;

class FakeWpService extends BaseFakeWpService
{
    private array $callLog = [];
    public array $registeredScripts = [];
    public array $enqueuedScripts = [];
    public array $localizedScripts = [];
    public array $filters = [];

    public function __construct(private $innerService, private array $methods = []) {}

    /**
     * Magic method to handle dynamic method calls.
     * This also logs the calls for verification.
     */
    public function __call(string $name, array $arguments)
    {
        $this->logCall($name, $arguments);

        if (isset($this->methods[$name])) {
            return ($this->methods[$name])(...$arguments);
        }

        if (method_exists($this->innerService, $name)) {
            return $this->innerService->{$name}(...$arguments);
        }

        throw new \BadMethodCallException("Method {$name} does not exist.");
    }

    private function logCall(string $method, array $arguments): void
    {
        $this->callLog[$method][] = $arguments;
    }

    public function getCallLog(string $method): array
    {
        return $this->callLog[$method] ?? [];
    }

    public function wasCalled(string $method): bool
    {
        return !empty($this->callLog[$method]);
    }

    public function wpRegisterScript(
        string $handle,
        string|false $src,
        array $deps = [],
        string|bool|null $ver = false,
        array|bool $args = []
    ): bool {
        $this->logCall('wpRegisterScript', func_get_args());
        $this->registeredScripts[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
            'args' => $args,
        ];
        return true;
    }

    public function wpAddInlineScript(string $handle, string $data, string $position = 'after'): bool
    {
        $this->logCall('wpAddInlineScript', func_get_args());
        return true;
    }

    public function getTemplateDirectoryUri(): string
    {
        return '/path/to/template';
    }

    public function getSiteUrl(?int $blogId = null, string $path = '', ?string $scheme = null): string
    {
        return 'https://test.test/';
    }

    public function addFilter(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1): true
    {
        $this->logCall('addFilter', func_get_args());
        return true;
    }

    public function wpLocalizeScript(string $handle, string $objectName, array $data): bool
    {
        $this->logCall('wpLocalizeScript', func_get_args());
        return true;
    }

    public function wpRegisterStyle(string $handle, string|false $src, array $deps = [], string|bool|null $ver = false, string $media = 'all'): bool
    {
        $this->logCall('wpRegisterStyle', func_get_args());
        return true;
    }
}