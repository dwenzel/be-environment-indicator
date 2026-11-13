<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 extension "be_environment_indicator".
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace DWenzel\BeEnvironmentIndicator\Backend\ToolbarItems;

use DWenzel\BeEnvironmentIndicator\Environment\Environment;
use DWenzel\BeEnvironmentIndicator\Environment\EnvironmentResolver;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment as CoreEnvironment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * Shows the current environment and, for admins, an admin marker in the
 * backend top bar. Colours the top bar according to the environment.
 */
class EnvironmentToolbarItem implements ToolbarItemInterface
{
    public const EXTENSION_KEY = 'be_environment_indicator';
    public const STYLESHEET = 'EXT:be_environment_indicator/Resources/Public/Css/indicator.css';
    /**
     * Top bar colour, TYPO3 11 and 12
     */
    public const TOPBAR_STYLESHEET_V11 = 'EXT:be_environment_indicator/Resources/Public/Css/topbar-v11.css';
    /**
     * Top bar colour, TYPO3 13 and later
     */
    public const TOPBAR_STYLESHEET_V13 = 'EXT:be_environment_indicator/Resources/Public/Css/topbar-v13.css';
    /**
     * ES module, TYPO3 12 and later (see Configuration/JavaScriptModules.php)
     */
    public const JAVASCRIPT_MODULE = '@dwenzel/be-environment-indicator/environment-indicator.js';
    /**
     * RequireJS module, TYPO3 11
     */
    public const REQUIREJS_MODULE = 'TYPO3/CMS/BeEnvironmentIndicator/EnvironmentIndicator';
    private const LLL = 'LLL:EXT:be_environment_indicator/Resources/Private/Language/locallang.xlf:';

    private ?Environment $environment = null;

    public function __construct(
        private readonly EnvironmentResolver $environmentResolver,
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly PageRenderer $pageRenderer
    ) {}

    /**
     * Visible for every backend user.
     */
    public function checkAccess(): bool
    {
        return $this->getBackendUser() instanceof BackendUserAuthentication;
    }

    public function getItem(): string
    {
        $environment = $this->getEnvironment();
        $this->pageRenderer->addCssInlineBlock(
            self::EXTENSION_KEY,
            $this->buildCss($environment->getColour())
        );
        $this->pageRenderer->addCssFile(self::STYLESHEET);
        $this->pageRenderer->addCssFile($this->getTopbarStylesheet());
        $this->loadJavaScript();

        $title = str_replace('%s', $environment->getContext(), $this->translate('context.title', 'Application context: %s'));
        $html = '<span class="be-environment-indicator" title="' . $this->escape($title) . '">'
            . '<span class="be-environment-indicator-label">' . $this->escape($environment->getLabel()) . '</span>';

        if ($this->isAdmin()) {
            $html .= ' <span class="be-environment-indicator-admin" title="'
                . $this->escape($this->translate('admin.title', 'You are logged in with administrator privileges'))
                . '">' . $this->escape($this->translate('admin', 'ADMIN')) . '</span>';
        }

        return $html . '</span>';
    }

    public function hasDropDown(): bool
    {
        return false;
    }

    public function getDropDown(): string
    {
        return '';
    }

    /**
     * @return array<string, string>
     */
    public function getAdditionalAttributes(): array
    {
        return [
            'class' => 'toolbar-item-be-environment-indicator',
            'data-environment' => $this->getEnvironment()->getIdentifier(),
        ];
    }

    public function getIndex(): int
    {
        return 0;
    }

    public function getEnvironment(): Environment
    {
        if ($this->environment === null) {
            $this->environment = $this->environmentResolver->resolve(
                $this->getApplicationContext(),
                $this->getConfiguration()
            );
        }

        return $this->environment;
    }

    /**
     * Only the environment colour is set inline; all rules live in
     * Resources/Public/Css/. The colour must already be
     * validated (see EnvironmentResolver).
     */
    protected function buildCss(string $colour): string
    {
        return ':root { --be-environment-indicator-colour: ' . $colour . '; }';
    }

    protected function getTopbarStylesheet(): string
    {
        return $this->getTypo3MajorVersion() >= 13 ? self::TOPBAR_STYLESHEET_V13 : self::TOPBAR_STYLESHEET_V11;
    }

    protected function loadJavaScript(): void
    {
        if ($this->getTypo3MajorVersion() >= 12) {
            $this->pageRenderer->loadJavaScriptModule(self::JAVASCRIPT_MODULE);
            return;
        }
        $this->pageRenderer->loadRequireJsModule(self::REQUIREJS_MODULE);
    }

    protected function getTypo3MajorVersion(): int
    {
        return (new Typo3Version())->getMajorVersion();
    }

    protected function getApplicationContext(): string
    {
        return (string)CoreEnvironment::getContext();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getConfiguration(): array
    {
        try {
            $configuration = $this->readExtensionConfiguration();
        } catch (\Exception $exception) {
            return [];
        }

        return is_array($configuration) ? $configuration : [];
    }

    protected function readExtensionConfiguration(): mixed
    {
        return $this->extensionConfiguration->get(self::EXTENSION_KEY);
    }

    protected function isAdmin(): bool
    {
        $backendUser = $this->getBackendUser();

        return $backendUser instanceof BackendUserAuthentication && $backendUser->isAdmin();
    }

    protected function translate(string $key, string $default): string
    {
        $languageService = $GLOBALS['LANG'] ?? null;
        if (!$languageService instanceof LanguageService) {
            return $default;
        }
        $translation = $languageService->sL(self::LLL . $key);

        return $translation !== '' ? $translation : $default;
    }

    protected function getBackendUser(): ?BackendUserAuthentication
    {
        $backendUser = $GLOBALS['BE_USER'] ?? null;

        return $backendUser instanceof BackendUserAuthentication ? $backendUser : null;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
