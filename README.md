# Backend Environment Indicator

TYPO3 extension (`be_environment_indicator`) which shows the current
environment in the backend top bar:

- The top bar background is coloured per environment.
- The environment name is shown next to the site name for every backend user.
- Admin users additionally see an orange "ADMIN" badge next to it.

The environment is derived from the TYPO3 application context
(`TYPO3_CONTEXT`). The extension has no database tables and no frontend
output.

## Requirements

| Version | TYPO3            | PHP                         |
|---------|------------------|-----------------------------|
| main    | 11.5, 12.4, 13.4 | 8.1 - 8.4 (13.4: 8.2 - 8.4) |
| 1.0.x   | 11.5             | 8.1 - 8.4                   |
| 0.1.x   | 11.5             | 8.1 - 8.4                   |

Support for TYPO3 14 is planned. Older TYPO3 versions will be
dropped where they conflict with newer core APIs.

## Installation

```bash
composer require dwenzel/be-environment-indicator
```

Flush the caches afterwards.

## Configuration

All settings are available in the extension configuration
(Admin Tools > Settings > Extension Configuration > be_environment_indicator).

| Environment   | Default context pattern | Default label | Default colour |
|---------------|-------------------------|---------------|----------------|
| `production`  | `Production`            | Production    | `#b91c1c`      |
| `staging`     | `Production/Staging`    | Staging       | `#c2410c`      |
| `development` | `Development`           | Development   | `#15803d`      |
| `testing`     | `Testing`               | Testing       | `#1d4ed8`      |

Additionally `neutralColour` (default `#4b5563`) is used for contexts that
match no pattern. In that case the label shows the raw context string.

### Matching rules

- A pattern matches the context itself and all its sub contexts:
  `Development` matches `Development` and `Development/Local`, but not
  `DevelopmentFoo`.
- Several patterns per environment can be given as comma-separated list,
  e.g. `Production/Staging, Production/Preview`.
- The matching pattern with the most path segments (number of `/`) wins, so
  `Production/Staging` is preferred over `Production`. On a tie the first
  environment in the order production, staging, development, testing wins.
- An empty pattern or label falls back to the default of that environment.

### Colours

Only hex colours in the form `#rgb` or `#rrggbb` are accepted. Any other
value is ignored and the default colour of the environment is used instead.

Configuration can also be set in `typo3conf/LocalConfiguration.php` or
`typo3conf/AdditionalConfiguration.php`. Missing keys fall back to the
defaults:

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['be_environment_indicator'] = [
    'staging' => [
        'context' => 'Production/Staging, Production/Preview',
        'label' => 'Preview',
        'colour' => '#c2410c',
    ],
];
```

### Previewing another environment locally

To see how a different environment looks, add the local context to its
patterns, e.g. with TYPO3 Console:

```bash
vendor/bin/typo3cms configuration:set \
  EXTENSIONS/be_environment_indicator/staging/context \
  'Production/Staging, Development/Local'
vendor/bin/typo3cms cache:flush
```

## How it works

- A backend toolbar item (`EnvironmentToolbarItem`) resolves the environment
  and renders the label and, for admins, the badge. TYPO3 11 registers it in
  `ext_localconf.php`, TYPO3 12 and later by service autoconfiguration.
- A small JavaScript module moves both next to the site name on the left of
  the top bar. It runs again after the core has refreshed the top bar.
  TYPO3 12 and later load the ES module
  `Resources/Public/JavaScript/environment-indicator.js` (import map in
  `Configuration/JavaScriptModules.php`), TYPO3 11 loads the RequireJS module
  `Resources/Public/JavaScript/EnvironmentIndicator.js`.
- All styles live in `Resources/Public/Css/`: `indicator.css` for label and
  badge, `topbar-v11.css` (TYPO3 11 and 12) or `topbar-v13.css` (TYPO3 13 and
  later) for the top bar colour. In TYPO3 13 and later the core's header
  colour variables are overridden and the header uses the dark colour scheme,
  independent of the backend theme. Only the environment colour is written
  inline into the page head, as custom property
  `--be-environment-indicator-colour`.
- If the JavaScript module cannot be loaded, the top bar is still coloured,
  but label and badge are not shown.

## Development

The package has its own Composer setup with the vendor directory `.build/`:

```bash
composer install
composer lint        # composer.json, editorconfig, PHP coding standards
composer sca         # PHPStan
composer test        # unit tests
composer fix         # fix composer.json, editorconfig and PHP style issues
composer migration   # TYPO3 Rector (use --dry-run to check only)
```

The GitHub workflows are described in
[.github/workflows/README.md](.github/workflows/README.md).

## Releases

Versions are tagged as `X.Y.Z`. A tag publishes the version to the TYPO3
Extension Repository (TER); Packagist picks it up through its GitHub
integration. The version in `ext_emconf.php` must match the tag.

## License

GPL-2.0-or-later
