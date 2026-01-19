<?php

declare(strict_types=1);

namespace App\Application\Contracts;

/**
 * Interface for generating unique identifiers.
 */
interface IdGeneratorInterface
{
    /**
     * Generate a unique identifier.
     *
     * @return string A unique ID
     */
    public function generate(): string;
}
