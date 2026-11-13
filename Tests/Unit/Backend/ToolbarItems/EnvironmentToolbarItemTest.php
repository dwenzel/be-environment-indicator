<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 extension "be_environment_indicator".
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace DWenzel\BeEnvironmentIndicator\Tests\Unit\Backend\ToolbarItems;

use DWenzel\BeEnvironmentIndicator\Backend\ToolbarItems\EnvironmentToolbarItem;
use DWenzel\BeEnvironmentIndicator\Environment\EnvironmentResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * @covers \DWenzel\BeEnvironmentIndicator\Backend\ToolbarItems\EnvironmentToolbarItem
 * @uses \DWenzel\BeEnvironmentIndicator\Environment\EnvironmentResolver
 * @uses \DWenzel\BeEnvironmentIndicator\Environment\Environment
 */
final class EnvironmentToolbarItemTest extends TestCase
{
    /**
     * @var ExtensionConfiguration&MockObject
     */
    private ExtensionConfiguration $extensionConfiguration;

    /**
     * @var PageRenderer&MockObject
     */
    private PageRenderer $pageRenderer;

    private string $addedCss = '';

    protected function setUp(): void
    {
        $this->extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $this->pageRenderer = $this->createMock(PageRenderer::class);
        $this->pageRenderer->method('addCssInlineBlock')->willReturnCallback(
            function (string $name, string $block): void {
                $this->addedCss .= $block;
            }
        );
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);
    }

    /**
     * @test
     */
    public function checkAccessReturnsTrueForAnyBackendUser(): void
    {
        $this->setBackendUser(false);

        self::assertTrue($this->createSubject('Production')->checkAccess());
    }

    /**
     * @test
     */
    public function checkAccessReturnsFalseWithoutBackendUser(): void
    {
        self::assertFalse($this->createSubject('Production')->checkAccess());
    }

    /**
     * @test
     */
    public function getItemShowsDevelopmentLabelWithoutAdminMarkerForEditor(): void
    {
        $this->setBackendUser(false);
        $this->extensionConfiguration->method('get')->willReturn([]);

        $item = $this->createSubject('Development/Local')->getItem();

        self::assertStringContainsString('>Development<', $item);
        self::assertStringNotContainsString('be-environment-indicator-admin', $item);
        self::assertStringContainsString('#15803d', $this->addedCss);
        self::assertStringContainsString('--be-environment-indicator-colour: #15803d', $this->addedCss);
    }

    /**
     * @test
     */
    public function getItemShowsStagingLabelWithAdminMarkerForAdmin(): void
    {
        $this->setBackendUser(true);
        $this->extensionConfiguration->method('get')->willReturn([]);

        $item = $this->createSubject('Production/Staging')->getItem();

        self::assertStringContainsString('>Staging<', $item);
        self::assertStringContainsString('be-environment-indicator-admin', $item);
        self::assertStringContainsString('>ADMIN<', $item);
        self::assertStringContainsString('#c2410c', $this->addedCss);
    }

    /**
     * @test
     */
    public function getItemShowsProductionLabelAndColour(): void
    {
        $this->setBackendUser(false);
        $this->extensionConfiguration->method('get')->willReturn([]);

        $item = $this->createSubject('Production')->getItem();

        self::assertStringContainsString('>Production<', $item);
        self::assertStringContainsString('#b91c1c', $this->addedCss);
    }

    /**
     * @test
     */
    public function getItemShowsRawContextWithNeutralColourForUnknownContext(): void
    {
        $this->setBackendUser(false);
        $this->extensionConfiguration->method('get')->willReturn([]);

        $subject = $this->createSubject('Foo/Bar');
        $item = $subject->getItem();

        self::assertStringContainsString('>Foo/Bar<', $item);
        self::assertStringContainsString(EnvironmentResolver::DEFAULT_NEUTRAL_COLOUR, $this->addedCss);
        self::assertSame('unknown', $subject->getAdditionalAttributes()['data-environment']);
    }

    /**
     * @test
     */
    public function getItemEscapesConfiguredLabel(): void
    {
        $this->setBackendUser(false);
        $this->extensionConfiguration->method('get')->willReturn([
            'production' => ['label' => '<script>alert(1)</script>'],
        ]);

        $item = $this->createSubject('Production')->getItem();

        self::assertStringNotContainsString('<script>', $item);
        self::assertStringContainsString('&lt;script&gt;', $item);
    }

    /**
     * @test
     */
    public function getItemDoesNotOutputInvalidColour(): void
    {
        $this->setBackendUser(false);
        $this->extensionConfiguration->method('get')->willReturn([
            'production' => ['colour' => 'red;}body{display:none'],
        ]);

        $this->createSubject('Production')->getItem();

        self::assertStringNotContainsString('body{', $this->addedCss);
        self::assertStringContainsString('#b91c1c', $this->addedCss);
    }

    /**
     * @test
     */
    public function getItemFollowsChangedConfiguration(): void
    {
        $this->setBackendUser(false);
        $this->extensionConfiguration->method('get')->willReturn([
            'development' => ['label' => 'Local', 'colour' => '#123456'],
        ]);

        $item = $this->createSubject('Development/Local')->getItem();

        self::assertStringContainsString('>Local<', $item);
        self::assertStringContainsString('#123456', $this->addedCss);
    }

    /**
     * @test
     */
    public function getItemUsesDefaultsIfExtensionIsNotConfigured(): void
    {
        $this->setBackendUser(false);
        $this->extensionConfiguration->method('get')->willThrowException(
            new ExtensionConfigurationExtensionNotConfiguredException('not configured', 1)
        );

        $item = $this->createSubject('Testing/Foo')->getItem();

        self::assertStringContainsString('>Testing<', $item);
        self::assertStringContainsString('#1d4ed8', $this->addedCss);
    }

    /**
     * @test
     */
    public function getItemUsesTranslatedLabelsForAdmin(): void
    {
        $this->setBackendUser(true);
        $this->extensionConfiguration->method('get')->willReturn([]);
        $lll = 'LLL:EXT:be_environment_indicator/Resources/Private/Language/locallang.xlf:';
        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->willReturnMap([
            [$lll . 'admin', 'MARKER_ADMIN'],
            [$lll . 'admin.title', 'MARKER_ADMIN_TITLE'],
            [$lll . 'context.title', 'MARKER_CONTEXT %s %s'],
        ]);
        $GLOBALS['LANG'] = $languageService;

        $item = $this->createSubject('Production/Staging')->getItem();

        self::assertStringContainsString('>MARKER_ADMIN<', $item);
        self::assertStringContainsString('title="MARKER_ADMIN_TITLE"', $item);
        self::assertStringContainsString('title="MARKER_CONTEXT Production/Staging Production/Staging"', $item);
    }

    /**
     * @test
     */
    public function getItemAddsStylesheetsForTypo3Version11And12(): void
    {
        $this->assertStylesheetsAdded(11, EnvironmentToolbarItem::TOPBAR_STYLESHEET_V11);
        $this->assertStylesheetsAdded(12, EnvironmentToolbarItem::TOPBAR_STYLESHEET_V11);
    }

    /**
     * @test
     */
    public function getItemAddsStylesheetsForTypo3Version13AndLater(): void
    {
        $this->assertStylesheetsAdded(13, EnvironmentToolbarItem::TOPBAR_STYLESHEET_V13);
        $this->assertStylesheetsAdded(14, EnvironmentToolbarItem::TOPBAR_STYLESHEET_V13);
    }

    /**
     * @test
     */
    public function stylesheetsExist(): void
    {
        $extensionPath = dirname(__DIR__, 4);
        $prefix = 'EXT:be_environment_indicator/';
        foreach ([EnvironmentToolbarItem::STYLESHEET, EnvironmentToolbarItem::TOPBAR_STYLESHEET_V11, EnvironmentToolbarItem::TOPBAR_STYLESHEET_V13] as $stylesheet) {
            self::assertStringStartsWith($prefix, $stylesheet);
            self::assertFileExists($extensionPath . '/' . substr($stylesheet, strlen($prefix)));
        }
    }

    private function assertStylesheetsAdded(int $typo3MajorVersion, string $topbarStylesheet): void
    {
        $this->setBackendUser(false);
        $pageRenderer = $this->createMock(PageRenderer::class);
        $addedFiles = [];
        $pageRenderer->method('addCssFile')->willReturnCallback(
            static function (string $file) use (&$addedFiles): void {
                $addedFiles[] = $file;
            }
        );
        $this->pageRenderer = $pageRenderer;

        $this->createSubject('Production', $typo3MajorVersion)->getItem();

        self::assertSame([EnvironmentToolbarItem::STYLESHEET, $topbarStylesheet], $addedFiles);
    }

    /**
     * @test
     */
    public function getItemLoadsRequireJsModuleInTypo3Version11(): void
    {
        if (!method_exists(PageRenderer::class, 'loadRequireJsModule')) {
            self::markTestSkipped('The installed TYPO3 version does not support RequireJS.');
        }
        $this->setBackendUser(false);
        $this->pageRenderer->expects(self::once())
            ->method('loadRequireJsModule')
            ->with(EnvironmentToolbarItem::REQUIREJS_MODULE);

        $this->createSubject('Production', 11)->getItem();
    }

    /**
     * @test
     */
    public function getItemLoadsJavaScriptModuleInTypo3Version12AndLater(): void
    {
        if (!method_exists(PageRenderer::class, 'loadJavaScriptModule')) {
            self::markTestSkipped('The installed TYPO3 version does not support ES modules.');
        }
        $this->setBackendUser(false);
        $this->pageRenderer->expects(self::once())
            ->method('loadJavaScriptModule')
            ->with(EnvironmentToolbarItem::JAVASCRIPT_MODULE);

        $this->createSubject('Production', 12)->getItem();
    }

    /**
     * @test
     */
    public function javaScriptModulesAreMappedToPublicJavaScriptFolder(): void
    {
        $extensionPath = dirname(__DIR__, 4);
        $configuration = require $extensionPath . '/Configuration/JavaScriptModules.php';
        $prefix = '@dwenzel/be-environment-indicator/';

        self::assertSame(
            'EXT:be_environment_indicator/Resources/Public/JavaScript/',
            $configuration['imports'][$prefix] ?? null
        );
        self::assertStringStartsWith($prefix, EnvironmentToolbarItem::JAVASCRIPT_MODULE);
        self::assertFileExists(
            $extensionPath . '/Resources/Public/JavaScript/' . substr(EnvironmentToolbarItem::JAVASCRIPT_MODULE, strlen($prefix))
        );
    }

    /**
     * @test
     */
    public function getIndexIsWithinAllowedRange(): void
    {
        $index = $this->createSubject('Production')->getIndex();

        self::assertGreaterThanOrEqual(0, $index);
        self::assertLessThanOrEqual(100, $index);
    }

    /**
     * @test
     */
    public function itemHasNoDropDown(): void
    {
        $subject = $this->createSubject('Production');

        self::assertFalse($subject->hasDropDown());
        self::assertSame('', $subject->getDropDown());
    }

    private function setBackendUser(bool $isAdmin): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->method('isAdmin')->willReturn($isAdmin);
        $GLOBALS['BE_USER'] = $backendUser;
    }

    private function createSubject(string $context, ?int $typo3MajorVersion = null): EnvironmentToolbarItem
    {
        $typo3MajorVersion ??= (new Typo3Version())->getMajorVersion();

        return new class ($context, $typo3MajorVersion, new EnvironmentResolver(), $this->extensionConfiguration, $this->pageRenderer) extends EnvironmentToolbarItem {
            public function __construct(
                private readonly string $context,
                private readonly int $typo3MajorVersion,
                EnvironmentResolver $environmentResolver,
                ExtensionConfiguration $extensionConfiguration,
                PageRenderer $pageRenderer
            ) {
                parent::__construct($environmentResolver, $extensionConfiguration, $pageRenderer);
            }

            protected function getApplicationContext(): string
            {
                return $this->context;
            }

            protected function getTypo3MajorVersion(): int
            {
                return $this->typo3MajorVersion;
            }

            /**
             * Skips loading if the installed TYPO3 version lacks the method
             * for the simulated version.
             */
            protected function loadJavaScript(): void
            {
                $method = $this->typo3MajorVersion >= 12 ? 'loadJavaScriptModule' : 'loadRequireJsModule';
                if (method_exists(PageRenderer::class, $method)) {
                    parent::loadJavaScript();
                }
            }
        };
    }
}
