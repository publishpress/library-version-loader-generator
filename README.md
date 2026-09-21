# library-version-loader-generator

Composer script that writes the newest-wins version loader for PublishPress prefixed libraries.

When several plugins ship the same prefixed package (`publishpress/psr-container`, `publishpress/pimple-pimple`, …), only the newest copy on the site should load. Same idea as Action Scheduler’s version registry.

**Usage (install, `extra.generator`, generated files, runtime, tests):** [Version Loader Generator](https://github.com/publishpress/team-handbook/blob/development/docs/dev/development/version-loader-generator.md) in the team handbook.

Prefixing walkthrough: [Prefixing namespaces](https://github.com/publishpress/team-handbook/blob/development/docs/dev/development/prefixing-libraries.md).

```bash
composer require --dev publishpress/version-loader-generator:"^1.0"
```

Binary: `vendor/bin/version-loader-generator`. Requires the library’s Composer `version` (four-digit).

## Develop this package

```bash
cp .env.example .env
composer install
composer test:unit
```

Unit tests copy `tests/_data/libraries/dummy-library`, run `bin/version-loader-generator`, and check the generated files.

## Release

1. Set `version` in `composer.json` (semver).
2. Add a `CHANGELOG.md` entry.
3. Merge to the default branch.
4. Create a GitHub release named with that version (for example `1.3.1`) so Packagist sees the tag.
