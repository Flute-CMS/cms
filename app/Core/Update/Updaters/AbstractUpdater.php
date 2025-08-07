<?php

namespace Flute\Core\Update\Updaters;

abstract class AbstractUpdater
{
    /**
     * Get current version
     */
    abstract public function getCurrentVersion(): string;

    /**
     * Get component identifier
     */
    abstract public function getIdentifier(): ?string;

    /**
     * Get component type
     */
    abstract public function getType(): string;

    /**
     * Get component name
     */
    abstract public function getName(): string;

    /**
     * Get component description
     */
    abstract public function getDescription(): string;

    /**
     * Process update
     */
    abstract public function update(array $data): bool;
}
