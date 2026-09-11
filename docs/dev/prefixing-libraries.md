# Prefixing namespaces for PHP library dependencies

Prefixed libraries are how we avoid conflicts with other plugins that ship different versions of the same third-party PHP libraries (Pimple, PSR-11 container, and similar).

We do not prefix per plugin. We prefix once under the `PublishPress\` namespace, publish that copy as a Composer package, and every plugin we own depends on it.

The prefixed library lives in its own GitHub repository and on [Packagist](https://packagist.org/). Repository names follow `library-<original-composer-name>` (slashes and capitals become `-`), for example [`library-psr-container`](https://github.com/publishpress/library-psr-container) and [`library-pimple-pimple`](https://github.com/publishpress/library-pimple-pimple). Packagist names are `publishpress/<name>`.

Prefixing is done with [Strauss](https://github.com/BrianHenryIE/strauss) (historically a Mozart replacement). Pin `brianhenryie/strauss` at `0.29.2`. Set `extra.strauss.target_directory` to `lib`: 0.29 defaults to `vendor-prefixed`. Existing library repos on `^0.14.0` should be moved to `0.29.2` with the [Strauss upgrade](#upgrade-strauss-on-an-existing-library) steps below.

The original library is a **pinned** `require-dev` dependency. Strauss copies and prefixes it into `lib/`. Configuration lives in `composer.json`.

When several of our plugins ship the same prefixed library, **only the most recent version should load**. That follows the same newest-wins idea as [Action Scheduler](https://github.com/publishpress/team-handbook/blob/master/docs/dev/action-scheduler/README.md), and is automated by this Version Loader Generator. Action Scheduler itself is **not** prefixed; see that page for why.

The generator writes `lib/include.php` and `lib/VersionLoader.php`. Those files register and initialize the library on `plugins_loaded` (register before initialize; typical priorities `-200` and `-190`). `VersionLoader` is aliased to `Versions` so a site that still has a 1.2.x copy of the same library shares one version registry. Plugins that consume the library should initialize on `plugins_loaded` at priority `-20` or later. Generator fields are listed under [Configure the version-loader generator](#configure-the-version-loader-generator).

We can still clash with old copies of our own plugins. Those we control: ship updates, or tell the site to update.

## How to prefix a new library

Walkthrough: prefix `psr/container` as `publishpress/psr-container`. Original namespace `Psr\Container` becomes `PublishPress\Psr\Container`. Current numbers: upstream `2.0.2`, prefixed `2.0.2.1`.

### Create a new repository on GitHub

Create a blank repository. **Do not clone the original library**. Name it `library-<original-name>` from the original `composer.json` `name` field. Slashes or capital letters become `-`.

Here the repo is `library-psr-container`.

Keep the original license and authors. Add PublishPress as a secondary author.

### Pin the original library

Put the original package in `require-dev` with a **fixed** version (no `^`, `~`, or other ranges). `composer update` then always fetches that exact copy into `vendor/` for Strauss.

```bash
composer require --dev psr/container:"2.0.2"
composer require --dev brianhenryie/strauss:"0.29.2"
composer require --dev publishpress/version-loader-generator:"^1.0"
```

`require-dev` should look like:

```json
{
    "require-dev": {
        "psr/container": "2.0.2",
        "brianhenryie/strauss": "0.29.2",
        "publishpress/version-loader-generator": "^1.0"
    }
}
```

If the library needs another package you already prefixed (Pimple needs PSR-11), put that **prefixed** package in `require`, not `require-dev`.

### Set the version number

The prefixed version is the upstream version plus a fourth digit for our build: `2.0.2` → `2.0.2.1`, `2.0.2.2`, and so on. Do not add a fifth digit unless we actually fork the code.

Set `version` in `composer.json` so the generator can name the loader functions in `include.php`:

```json
{
    "version": "2.0.2.1"
}
```

### Configure Strauss

Add `extra.strauss`. Output must go to `lib` (Strauss 0.29 defaults to `vendor-prefixed`). Prefix keys match [library-psr-container](https://github.com/publishpress/library-psr-container/blob/2.0.2.x/composer.json):

```json
{
    "extra": {
        "strauss": {
            "target_directory": "lib",
            "namespace_prefix": "PublishPress\\",
            "classmap_prefix": "PublishPress_",
            "constant_prefix": "PUBLISHPRESS_",
            "include_author": true,
            "classmap_output": true,
            "packages": [
                "psr/container"
            ],
            "delete_vendor_packages": true,
            "delete_vendor_files": true
        }
    }
}
```

Change `packages` to the upstream Composer name you are prefixing. That list is required: Strauss 0.29 otherwise prefixes packages from `require`, and the original library is in `require-dev`. Use JSON booleans (`true`), not the strings (`"true"`) some 0.14 configs still have.

### Configure the version-loader generator

Add `extra.generator` next to `extra.strauss`. `lib-class-test` is a PHP fragment that detects whether this prefixed library is already loaded:

```json
{
    "extra": {
        "generator": {
            "lib-class-test": "interface_exists('PublishPress\\Psr\\Container\\ContainerInterface')",
            "action-register-priority": "-200",
            "action-initialize-priority": "-190"
        }
    }
}
```

Use `class_exists(...)` when the library’s entry point is a class (Pimple uses `class_exists('PublishPress\\Pimple\\Container')`). Keep register priority before initialize.

* `lib-class-test`: PHP fragment that checks whether this prefixed library is already loaded. **Required.**
* `action-register-priority`: negative integer for the `plugins_loaded` action that **registers** the library. Default `-200`. Registration must run before initialization.
* `action-initialize-priority`: negative integer for the `plugins_loaded` action that **initializes** the library. Default `-190`. Initialization must run before the plugin that uses the library. A nested library should initialize later than its dependency (Pimple uses `-185` vs PSR-11 `-190`).
* `src-dir`: directory for `include.php` and `VersionLoader.php` (default `lib`).
* `namespace`: PHP namespace for the generated loader (inferred from `name` when omitted).
* `generate-test-file`: whether to write the Integration Cest (default `true`).
* `test-file`: path for that Cest (default `tests/codeception/Integration/VersionLoaderCest.php`).

Once installed, the binary is `vendor/bin/version-loader-generator`.

### Composer scripts

Run Strauss, then the generator, on every `composer install` / `composer update`:

```json
{
    "scripts": {
        "strauss": [
            "vendor/bin/strauss"
        ],
        "generate-files": [
            "vendor/bin/version-loader-generator"
        ],
        "post-install-cmd": [
            "@strauss",
            "@generate-files"
        ],
        "post-update-cmd": [
            "@strauss",
            "@generate-files"
        ],
        "test:unit": "vendor/bin/codecept run Unit",
        "test:integration": "vendor/bin/codecept run Integration"
    }
}
```

Then:

```bash
composer update
```

Expected result: prefixed PHP in `lib/` (namespace `PublishPress\Psr\Container`), plus generated `lib/include.php` and `lib/VersionLoader.php`. Strauss 0.29 also copies the rest of the upstream package (`LICENSE`, `README.md`, the original `composer.json`, `.gitignore`) and writes a Composer autoloader under `lib/composer/`, replacing `lib/autoload.php`. That is expected. `include.php` still loads `lib/autoload.php`. The copied `composer.json` may still mention the unprefixed namespace; runtime autoload uses `lib/composer/`, not that file. Keep `LICENSE`.

If Strauss writes to `vendor-prefixed` instead of `lib`, set `target_directory` to `lib`. If namespaces come out double-prefixed (`PublishPress\PublishPress\...`), Strauss ran against already-prefixed sources — start from the original package in `vendor/`, not from `lib/`.

### Register the autoloader

`include.php` does not exist until the generator has run. After the first successful `composer update`, add it to `autoload.files`:

```json
{
    "autoload": {
        "files": [
            "lib/include.php"
        ]
    }
}
```

```bash
composer dump-autoload
```

New packages also need a Packagist submission (existing ones already have the GitHub webhook).

### Test

Match [publishpress-cart](https://github.com/publishpress/publishpress-cart/blob/development/tests/README.md): Codeception suites under `tests/codeception/`, params from `.env` (copy `.env.example`). Do not use the old `wpunit` suite or `.env.testing`.

```bash
# PHP unit tests (no WordPress)
composer test:unit

# PHP integration tests (WordPress + WPLoader)
composer test:integration
```

Version-loader and newest-wins checks need `plugins_loaded`, so they belong in **Integration**, not Unit. Point `codeception.yml` at Cart’s layout:

```yaml
paths:
    tests: tests/codeception
params:
    - .env
```

Integration WPLoader should read Cart’s `WP_TESTS_*` keys (`WP_TESTS_ROOT_DIR`, `WP_TESTS_DB_URL`, `WP_TESTS_TEST_TABLE_PREFIX`, `WP_TESTS_DOMAIN`). Activate the fixture plugins that exercise several copies of the library (the old `tests/_data/plugins/` trees). If the repo uses `publishpress/dev-workspace`, start the test stack with `composer test:up` before Integration.

The version-loader generator writes `tests/codeception/Integration/VersionLoaderCest.php` (`IntegrationTester`). Override the path with `extra.generator.test-file` if needed. Filter a single test the Cart way:

```bash
composer test:integration VersionLoaderCest:testInitializeLatestVersion
```

Libraries do not need Cart’s Playwright suites (`test:regression`, `test:admin`).

## Use in a plugin

In the plugin (or another library), remove the original package from Composer and require the prefixed one, for example `publishpress/psr-container`. Rewrite code and strings that mention the original namespace to the prefixed namespace (`Psr\Container` → `PublishPress\Psr\Container`).

Initialize the plugin on `plugins_loaded` at priority `-20` or later so the version loader has already picked the newest copy.

## How to update a prefixed library

1. Change the pinned upstream version in `require-dev` to the target (still a fixed constraint).
2. Set `version` to that upstream version plus the next fourth digit (`2.0.2` → `2.0.2.1` for a first prefixed build, or increment the fourth digit for another prefixing pass of the same upstream).
3. Run `composer update`. Strauss prefixes into `lib/`; the generator refreshes `include.php` and `VersionLoader.php`.
4. Copy `.env.example` to `.env` (fill `WP_TESTS_*`). Run `composer test:unit`, then `composer test:integration`. Version-loader tests are Integration. Without `.env`, Codeception exits immediately.
5. Review the prefixed code in `lib/` by hand. On Strauss 0.29, extra package files and `lib/composer/` are normal; see the expected result above.
6. Commit.
7. Create a GitHub release named with the four-digit version (for example `2.0.2.1`) so Packagist sees the tag.

Then run `composer update` in the plugins that consume the package.

If the library depends on another prefixed package, follow the nested-dependency notes in the Pimple reference below. On Strauss 0.29, also clean `lib/composer/` autoload maps, not only `lib/autoload-classmap.php`.

## Upgrade Strauss on an existing library

Existing repos such as [library-psr-container](https://github.com/publishpress/library-psr-container) still pin `brianhenryie/strauss` at `^0.14.0`. The Strauss config (`target_directory`, prefixes, `packages`, Composer hooks, generator) is already in place. To move one of those repos to `0.29.2`:

1. Set `"brianhenryie/strauss": "0.29.2"` in `require-dev` (exact pin, no `^`).
2. Keep `extra.strauss.target_directory` as `lib`. Drop empty Mozart-era keys if present (`override_autoload`, empty `exclude_*` objects). Change `include_author` and `classmap_output` to JSON booleans.
3. Increment the fourth digit of `version`. Same upstream, new prefixing pass: `2.0.2.1` → `2.0.2.2`.
4. Run `composer update`. Strauss 0.29 then the generator run from `post-update-cmd`.
5. Review `lib/`. Namespaces should stay `PublishPress\…` (not double-prefixed). Expect the extra files and Composer autoloader described above. For a PSR-0 library such as Pimple, Strauss 0.29 also writes `src/PublishPress/Pimple/` and may leave the old `src/Pimple/` tree — delete the leftover.
6. Nested libraries: extend `scripts/post-update.php` so it strips `PublishPress\Psr\Container` from `lib/composer/autoload_classmap.php`, `autoload_psr4.php`, and `autoload_static.php`. Deleting `lib/psr` and editing `lib/autoload-classmap.php` is not enough; runtime `lib/autoload.php` now loads `lib/composer/`.
7. Copy `.env.example` to `.env` if needed, then run `composer test:unit` and `composer test:integration`.
8. Commit and GitHub-release as in the update checklist.

## Reference implementations

**Simple (this walkthrough):** [library-psr-container](https://github.com/publishpress/library-psr-container) prefixes `psr/container` and publishes `publishpress/psr-container`. Use its `composer.json` for Strauss, generator, and Composer hooks. Its [README](https://github.com/publishpress/library-psr-container#how-to-update-the-prefixed-library) matches the update checklist above. Current release: `2.0.2.1`.

**Nested dependency:** [library-pimple-pimple](https://github.com/publishpress/library-pimple-pimple) prefixes `pimple/pimple` as `publishpress/pimple-pimple`. Put the already-prefixed package in `require` (`publishpress/psr-container`). List **both** `pimple/pimple` and `psr/container` in `extra.strauss.packages` so Strauss rewrites `Psr\Container` to `PublishPress\Psr\Container` inside Pimple. Then a `post-update.php` hook (after `@strauss`, before `@generate-files`) deletes `lib/psr` so the types come from `publishpress/psr-container`, not a second copy.

Initialize Pimple later than PSR-11 (`action-initialize-priority` `-185` vs `-190`). Strauss 0.29 still copies `psr/container` into `lib/` and maps it in `lib/composer/`. The 0.14 `post-update.php` only edits `lib/autoload-classmap.php`, which 0.29 no longer uses at runtime. Until that script also strips PSR-11 from `lib/composer/autoload_*.php`, loading `PublishPress\Pimple\Psr11\Container` before `publishpress/psr-container` fatals on a missing `lib/psr/container/src/ContainerInterface.php`. With the intended hook order it works because the interface is already loaded.

Pimple is PSR-0 (`"Pimple": "src/"`). After 0.29 the live classes are under `lib/pimple/pimple/src/PublishPress/Pimple/`. See its [README](https://github.com/publishpress/library-pimple-pimple#how-to-update-the-prefixed-library) when updating a library that depends on another prefixed library.
