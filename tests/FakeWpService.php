<?php
declare(strict_types=1);

namespace WpService;

class FakeWpService
{
    public array $registeredScripts = [];
    public array $enqueuedScripts = [];
    public array $localizedScripts = [];
    public array $filters = [];

    public function wpRegisterScript($handle, $src, $deps, $ver, $inFooter)
    {
        $this->registeredScripts[$handle] = compact('src', 'deps', 'ver', 'inFooter');
    }

    public function wpEnqueueScript($handle)
    {
        $this->enqueuedScripts[] = $handle;
    }

    public function wpLocalizeScript($handle, $objectName, $data)
    {
        $this->localizedScripts[$handle][$objectName] = $data;
    }

    public function wpRegisterStyle($handle, $src, $deps, $ver)
    {
        // Simulate style registration
    }

    public function wpEnqueueStyle($handle)
    {
        // Simulate style enqueue
    }

    public function addFilter($tag, $callback, $priority, $acceptedArgs)
    {
        $this->filters[$tag][] = compact('callback', 'priority', 'acceptedArgs');
    }

    public function getTemplateDirectoryUri()
    {
        return '/fake/theme/uri/';
    }
}
