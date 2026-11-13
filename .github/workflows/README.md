# GitHub Actions workflows

This file lives in `.github/workflows/`, not in `.github/`: GitHub shows a
`README.md` in `.github/` on the repository start page instead of the root
`README.md`.

## Workflows

* Tests (`tests.yml`): unit tests with TYPO3 11.5, 12.4, 13.4 and 14.3 and
  PHP 8.1 to 8.4 (TYPO3 13.4 and 14.3: PHP 8.2 to 8.4)
* Code Quality (`code-quality.yml`): PHP-CS-Fixer, PHPStan, EditorConfig
* Composer Validation (`composer-validate.yml`): `composer validate`,
  normalized `composer.json`
* TYPO3 Compatibility (`typo3-compatibility.yml`): TYPO3 Rector (dry run)
  with each supported TYPO3 version
* Publish to TER (`publish.yml`): on version tags (`X.Y.Z`) or started
  manually for an existing tag; checks the tag against `ext_emconf.php` and
  uploads the version with [tailor](https://github.com/TYPO3/tailor). Needs
  the repository secret `TYPO3_API_TOKEN`; without it, the job only reports
  that nothing was published.

Packagist is updated by its GitHub integration, not by a workflow.

## Triggers

* pushes to `main`, `feature/**` and `support/**`
* all pull requests

## Supported TYPO3 versions

The matrices in `tests.yml` and `typo3-compatibility.yml` contain TYPO3
11.5, 12.4, 13.4 and 14.3. Code Quality runs with the newest TYPO3 version
that Composer resolves.

The same checks run locally with `composer lint`, `composer sca`,
`composer test` and `composer migration:rector -- --dry-run`. To run them
against a specific TYPO3 version:

```bash
composer require typo3/cms-core:^13.4 typo3/cms-backend:^13.4 --no-update
composer update -W
git checkout composer.json
```

The development tools allow two major versions each (PHPStan 1 and 2,
saschaegerer/phpstan-typo3 1 to 3, ssch/typo3-rector 2 and 3), because no
single version supports TYPO3 11 and 14.

## Security advisories

All public TYPO3 11.5 releases on Packagist are affected by security
advisories (fixed releases are only available via ELTS). Composer 2.9+
refuses to install them by default, so `composer.json` ignores the advisories
for the affected core packages (`config.policy.advisories.ignore`). This only
affects the development setup of this package, not projects requiring it.
Remove the entries once TYPO3 11.5 support is dropped.
