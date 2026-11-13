<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 extension "be_environment_indicator".
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace DWenzel\BeEnvironmentIndicator\Tests\Unit\Environment;

use DWenzel\BeEnvironmentIndicator\Environment\Environment;
use DWenzel\BeEnvironmentIndicator\Environment\EnvironmentResolver;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DWenzel\BeEnvironmentIndicator\Environment\EnvironmentResolver
 * @covers \DWenzel\BeEnvironmentIndicator\Environment\Environment
 */
final class EnvironmentResolverTest extends TestCase
{
    private EnvironmentResolver $subject;

    protected function setUp(): void
    {
        $this->subject = new EnvironmentResolver();
    }

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function defaultMappingDataProvider(): array
    {
        return [
            'Production' => ['Production', 'production', 'Production', '#b91c1c'],
            'Production sub context' => ['Production/Live', 'production', 'Production', '#b91c1c'],
            'Production/Staging' => ['Production/Staging', 'staging', 'Staging', '#c2410c'],
            'Production/Staging sub context' => ['Production/Staging/Server1', 'staging', 'Staging', '#c2410c'],
            'Development' => ['Development', 'development', 'Development', '#15803d'],
            'Development/Local' => ['Development/Local', 'development', 'Development', '#15803d'],
            'Testing' => ['Testing', 'testing', 'Testing', '#1d4ed8'],
            'Testing/Acceptance' => ['Testing/Acceptance', 'testing', 'Testing', '#1d4ed8'],
        ];
    }

    /**
     * @test
     * @dataProvider defaultMappingDataProvider
     */
    public function resolveMapsContextWithDefaultConfiguration(
        string $context,
        string $expectedIdentifier,
        string $expectedLabel,
        string $expectedColour
    ): void {
        $environment = $this->subject->resolve($context);

        self::assertSame($expectedIdentifier, $environment->getIdentifier());
        self::assertSame($expectedLabel, $environment->getLabel());
        self::assertSame($expectedColour, $environment->getColour());
        self::assertSame($context, $environment->getContext());
        self::assertTrue($environment->isKnown());
    }

    /**
     * @test
     */
    public function resolveReturnsNeutralEnvironmentWithRawContextForUnknownContext(): void
    {
        $configuration = $this->getDefaultConfiguration();
        $configuration['testing']['context'] = 'Testing/Bar';

        $environment = $this->subject->resolve('Testing/Foo', $configuration);

        self::assertSame(Environment::IDENTIFIER_UNKNOWN, $environment->getIdentifier());
        self::assertFalse($environment->isKnown());
        self::assertSame('Testing/Foo', $environment->getLabel());
        self::assertSame('#4b5563', $environment->getColour());
    }

    /**
     * @test
     */
    public function resolveUsesConfiguredNeutralColourForUnknownContext(): void
    {
        $environment = $this->subject->resolve('Foo', ['neutralColour' => '#abc']);

        self::assertSame('#abc', $environment->getColour());
        self::assertSame('Foo', $environment->getLabel());
    }

    /**
     * @test
     */
    public function resolveFallsBackToDefaultNeutralColourForInvalidNeutralColour(): void
    {
        $environment = $this->subject->resolve('Foo', ['neutralColour' => 'red;}body{']);

        self::assertSame(EnvironmentResolver::DEFAULT_NEUTRAL_COLOUR, $environment->getColour());
    }

    /**
     * @test
     */
    public function resolveDoesNotMatchContextWhichOnlySharesAPrefix(): void
    {
        $environment = $this->subject->resolve('ProductionFoo');

        self::assertFalse($environment->isKnown());
    }

    /**
     * @test
     */
    public function resolveUsesConfiguredLabelAndColour(): void
    {
        $configuration = $this->getDefaultConfiguration();
        $configuration['staging']['label'] = 'Preview';
        $configuration['staging']['colour'] = '#FF00AA';

        $environment = $this->subject->resolve('Production/Staging', $configuration);

        self::assertSame('staging', $environment->getIdentifier());
        self::assertSame('Preview', $environment->getLabel());
        self::assertSame('#FF00AA', $environment->getColour());
    }

    /**
     * @test
     */
    public function resolveUsesConfiguredContextPatterns(): void
    {
        $configuration = $this->getDefaultConfiguration();
        $configuration['staging']['context'] = 'Production/Staging, Production/Preview';

        $environment = $this->subject->resolve('Production/Preview', $configuration);

        self::assertSame('staging', $environment->getIdentifier());
    }

    /**
     * @test
     */
    public function resolvePrefersMostSpecificMatch(): void
    {
        $configuration = $this->getDefaultConfiguration();
        $configuration['testing']['context'] = 'Development/Local/Docker';

        self::assertSame('testing', $this->subject->resolve('Development/Local/Docker', $configuration)->getIdentifier());
        self::assertSame('development', $this->subject->resolve('Development/Local', $configuration)->getIdentifier());
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidColourDataProvider(): array
    {
        return [
            'css injection' => ['red;}body{'],
            'named colour' => ['red'],
            'missing hash' => ['ff0000'],
            'wrong length' => ['#ff00'],
            'non hex' => ['#gggggg'],
            'trailing payload' => ["#ff0000\n}body{"],
            'empty' => [''],
            'array' => [['#ff0000']],
        ];
    }

    /**
     * @test
     * @dataProvider invalidColourDataProvider
     */
    public function resolveFallsBackToDefaultColourOfEnvironmentForInvalidColour(mixed $colour): void
    {
        $configuration = $this->getDefaultConfiguration();
        $configuration['production']['colour'] = $colour;

        $environment = $this->subject->resolve('Production', $configuration);

        self::assertSame('#b91c1c', $environment->getColour());
    }

    /**
     * @test
     */
    public function resolveFallsBackToDefaultsForEmptyLabelAndContext(): void
    {
        $configuration = $this->getDefaultConfiguration();
        $configuration['development']['context'] = '';
        $configuration['development']['label'] = '  ';

        $environment = $this->subject->resolve('Development/Local', $configuration);

        self::assertSame('development', $environment->getIdentifier());
        self::assertSame('Development', $environment->getLabel());
    }

    /**
     * @test
     */
    public function resolveIgnoresMalformedConfiguration(): void
    {
        $environment = $this->subject->resolve('Production', ['production' => 'invalid', 'neutralColour' => []]);

        self::assertSame('production', $environment->getIdentifier());
        self::assertSame('#b91c1c', $environment->getColour());
    }

    /**
     * @test
     */
    public function isValidColourAcceptsShortAndLongHexColours(): void
    {
        self::assertTrue($this->subject->isValidColour('#abc'));
        self::assertTrue($this->subject->isValidColour('#A1B2C3'));
        self::assertFalse($this->subject->isValidColour('#abcd'));
        self::assertFalse($this->subject->isValidColour("#abcdef\n"));
        self::assertFalse($this->subject->isValidColour(null));
    }

    /**
     * @return array<string, array<string, string>|string>
     */
    private function getDefaultConfiguration(): array
    {
        return EnvironmentResolver::DEFAULT_ENVIRONMENTS + ['neutralColour' => EnvironmentResolver::DEFAULT_NEUTRAL_COLOUR];
    }
}
