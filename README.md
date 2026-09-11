# library-version-loader-generator

Composer script that writes the newest-wins version loader for PublishPress prefixed libraries.

Usage in a prefixed library: [docs/dev/prefixing-libraries.md](docs/dev/prefixing-libraries.md).

## Develop

```bash
cp .env.example .env   # once, for the local workspace
composer install
composer test:unit
```

Unit tests copy `tests/_data/libraries/dummy-library`, run `bin/version-loader-generator`, and check the generated files.

## Release

1. Set `version` in `composer.json` (semver).
2. Add a `CHANGELOG.md` entry.
3. Merge to the default branch.
4. Create a GitHub release named with that version (for example `1.3.0`) so Packagist sees the tag.
