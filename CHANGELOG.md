The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

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
