<?php

declare(strict_types=1);

namespace WpUtilService\Exceptions;

/**
 * Raised when one WpUtilService instance is used for multiple WordPress roots.
 */
class EnqueueRootConflictException extends \RuntimeException
{
    public function __construct(string $configuredRoot, string $requestedRoot)
    {
        parent::__construct(
            "This WpUtilService instance is already configured for '{$configuredRoot}', "
            . "but '{$requestedRoot}' resolves to a different theme or plugin root. "
            . 'Create a separate WpUtilService instance for the new root and call enqueue() on that instance.',
        );
    }
}
