<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 extension "be_environment_indicator".
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace DWenzel\BeEnvironmentIndicator\Environment;

/**
 * Resolved environment for a given application context.
 *
 * Label and colour are not escaped. The colour is guaranteed to be a valid
 * hex colour (#rgb or #rrggbb) by the resolver.
 */
final class Environment
{
    public const IDENTIFIER_UNKNOWN = 'unknown';

    public function __construct(
        private readonly string $identifier,
        private readonly string $label,
        private readonly string $colour,
        private readonly string $context
    ) {}

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getColour(): string
    {
        return $this->colour;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function isKnown(): bool
    {
        return $this->identifier !== self::IDENTIFIER_UNKNOWN;
    }
}
