<?php

declare(strict_types=1);

namespace WpUtilService\Tests;

class LoggedFakeWpService
{
    private array $callLog = [];

    public function __construct(private FakeWpService $service) {}

    public function __call(string $name, array $arguments)
    {
        $this->logCall($name, $arguments);

        if (method_exists($this->service, $name)) {
            return $this->service->{$name}(...$arguments);
        }

        throw new \BadMethodCallException("Method {$name} does not exist in the wrapped service.");
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
}