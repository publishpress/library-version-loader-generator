The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [1.3.0] - Sep 11, 2026

- Rename generated class/file from `Versions` / `Versions.php` to `VersionLoader` / `VersionLoader.php`.
- Write the generated Cest to `tests/codeception/Integration/VersionLoaderCest.php` (Cart layout) instead of `tests/wpunit/VersionsCest.php`.
- Type-hint `IntegrationTester` instead of `WpunitTester`.
- Add `extra.generator.test-file` to override the Cest path.
- Remove leftover `Versions.php` and `VersionsCest.php` files when generating the new names.
- Log the real `src-dir` when writing `include.php` and `VersionLoader.php`.
- Default `action-register-priority` / `action-initialize-priority` to `-200` / `-190`.
- Always remove leftover `VersionsCest.php`, even when `generate-test-file` is false.
- Point this repo’s Codeception params at `.env` instead of `.env.testing`.
- Pin `behat/gherkin` below 4.13 so Codeception 4 can still load `i18n.php`.
- Add usage docs at `docs/dev/prefixing-libraries.md` (copied from the team handbook).

## [1.2.4] - Jun 22, 2025

- Improved version detection to sequentially check "version", "extra.version", and "extra.generator.version" properties in composer.json.

## [1.2.3] - Jun 22, 2025

- Fix PHP warning when used with stable version releases.

## [1.2.2] - Jun 14, 2025

- Fix the support for pre-release version numbers that contain: alpha, beta, rc.

## [1.2.1] - Jun 03, 2025

- Added ability to control test file generation through extra.generator.generate-test-file configuration option in composer.json.

## [1.2.0] - Jun 03, 2025

### Added

- Added ability to customize namespace through `extra.generator.namespace` in composer.json configuration.
