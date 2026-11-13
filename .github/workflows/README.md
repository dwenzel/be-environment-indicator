# GitHub Actions workflows

This file lives in `.github/workflows/`, not in `.github/`: GitHub shows a
`README.md` in `.github/` on the repository start page instead of the root
`README.md`.

## Workflows

* Tests (`tests.yml`): unit tests with TYPO3 11.5 and PHP 8.1 to 8.4
* Code Quality (`code-quality.yml`): PHP-CS-Fixer, PHPStan, EditorConfig
* Composer Validation (`composer-validate.yml`): `composer validate`,
  normalized `composer.json`
* TYPO3 Compatibility (`typo3-compatibility.yml`): TYPO3 Rector (dry run)
  with TYPO3 11.5
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

The matrices currently contain TYPO3 11.5 only. Newer versions are added to
`tests.yml` and `typo3-compatibility.yml` as support for them is
implemented.

The same checks run locally with `composer lint`, `composer sca`,
`composer test` and `composer migration:rector -- --dry-run`.
