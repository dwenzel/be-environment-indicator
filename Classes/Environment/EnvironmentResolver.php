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
 * Maps an application context string to an environment (label and colour).
 *
 * Configuration structure (as provided by the extension configuration):
 *
 *   [
 *       'production' => ['context' => 'Production', 'label' => 'Production', 'colour' => '#b91c1c'],
 *       ...
 *       'neutralColour' => '#4b5563',
 *   ]
 *
 * A pattern matches a context if it is equal to it or if the context is a
 * sub context of it ("Development" matches "Development/Local"). Several
 * patterns per environment can be given as comma-separated list. The matching
 * pattern with the most path segments (number of "/") wins. On a tie the
 * first environment in the order production, staging, development, testing
 * wins.
 */
class EnvironmentResolver
{
    public const DEFAULT_ENVIRONMENTS = [
        'production' => [
            'context' => 'Production',
            'label' => 'Production',
            'colour' => '#b91c1c',
        ],
        'staging' => [
            'context' => 'Production/Staging',
            'label' => 'Staging',
            'colour' => '#c2410c',
        ],
        'development' => [
            'context' => 'Development',
            'label' => 'Development',
            'colour' => '#15803d',
        ],
        'testing' => [
            'context' => 'Testing',
            'label' => 'Testing',
            'colour' => '#1d4ed8',
        ],
    ];

    public const DEFAULT_NEUTRAL_COLOUR = '#4b5563';

    private const COLOUR_PATTERN = '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/D';

    /**
     * @param array<string, mixed> $configuration
     */
    public function resolve(string $context, array $configuration = []): Environment
    {
        $context = trim($context);
        $bestIdentifier = null;
        $bestSpecificity = -1;

        foreach (self::DEFAULT_ENVIRONMENTS as $identifier => $defaults) {
            $environmentConfiguration = $this->getEnvironmentConfiguration($configuration, $identifier);
            $patterns = $this->getPatterns($environmentConfiguration['context'] ?? '', $defaults['context']);
            foreach ($patterns as $pattern) {
                if (!$this->matches($pattern, $context)) {
                    continue;
                }
                $specificity = substr_count($pattern, '/');
                if ($specificity > $bestSpecificity) {
                    $bestSpecificity = $specificity;
                    $bestIdentifier = $identifier;
                }
            }
        }

        if ($bestIdentifier === null) {
            return new Environment(
                Environment::IDENTIFIER_UNKNOWN,
                $context,
                $this->sanitizeColour($configuration['neutralColour'] ?? null, self::DEFAULT_NEUTRAL_COLOUR),
                $context
            );
        }

        $defaults = self::DEFAULT_ENVIRONMENTS[$bestIdentifier];
        $environmentConfiguration = $this->getEnvironmentConfiguration($configuration, $bestIdentifier);
        $label = trim((string)($environmentConfiguration['label'] ?? ''));

        return new Environment(
            $bestIdentifier,
            $label !== '' ? $label : $defaults['label'],
            $this->sanitizeColour($environmentConfiguration['colour'] ?? null, $defaults['colour']),
            $context
        );
    }

    public function isValidColour(mixed $colour): bool
    {
        return is_string($colour) && preg_match(self::COLOUR_PATTERN, $colour) === 1;
    }

    private function sanitizeColour(mixed $colour, string $default): string
    {
        if (is_string($colour)) {
            $colour = trim($colour);
        }

        return $this->isValidColour($colour) ? (string)$colour : $default;
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<string, mixed>
     */
    private function getEnvironmentConfiguration(array $configuration, string $identifier): array
    {
        $environmentConfiguration = $configuration[$identifier] ?? [];

        return is_array($environmentConfiguration) ? $environmentConfiguration : [];
    }

    /**
     * @return list<string>
     */
    private function getPatterns(mixed $configuredPatterns, string $default): array
    {
        $configuredPatterns = is_string($configuredPatterns) ? $configuredPatterns : '';
        $patterns = array_values(array_filter(
            array_map(static fn(string $pattern): string => trim($pattern, " \t\n\r\0\x0B/"), explode(',', $configuredPatterns)),
            static fn(string $pattern): bool => $pattern !== ''
        ));

        return $patterns !== [] ? $patterns : [$default];
    }

    private function matches(string $pattern, string $context): bool
    {
        return $context === $pattern || str_starts_with($context, $pattern . '/');
    }
}
