# library-version-loader-generator
Script to auto generate scripts and test for libraries on PublishPress.

The script will generate three files in the library:

- `lib/Versions.php`
- `lib/include.php`
- `tests/wpunit/VersionsCest.php`

## How to use

This library should be included in the target library as dev requirement:

```bash
composer require --dev publishpress/version-loader-generator
```

Once installed it will be available in the `vendor/bin` folder.

Its requires a few settings in the `composer.json` file to correctly generate the files:

```json
{
  "extra": {
    "generator": {
      "lib-class-test": "interface_exists('PublishPress\\Psr\\Container\\ContainerInterface')",
      "action-initialize-priority": "-190",
      "action-register-priority": "-200"
    }
  }
}
```

* `lib-class-test`: this is a fragment of PHP code that will check if the target library is loaded or not.
* `action-register-priority`: a negative integer that will be used as the priority of the `plugins_loaded` action for registering the library. The registration should always be done before the initialization.
* `action-intialize-priority`: a negative integer that will be used as the priority of the `plugins_loaded` action for initializing the library. The initialization should always be done before the plugin using the library is initialized.

We advise the plugins using the prefixed libraries to use the `plugins_loaded` action with a priority of `-20` or higher to initialize the plugin instead of initializing it on run time.

Add a composer script to run the generator on every update/install. On the following example we are using Strauss also, to prefix the respective library:

```json
{
  "scripts": {
    "strauss": [
      "vendor/bin/strauss"
    ],
    "generate-files": "vendor/bin/version-loader-generator",
    "post-install-cmd": [
      "@strauss",
      "@generate-files"
    ],
    "post-update-cmd": [
      "@strauss",
      "@generate-files"
    ]
  }
}
```



## How to test this script

```bash
vendor/bin/codecept run unit
```

## How to test target libraries

Make sure Codeception is properly configured in the target library, for running WPUnit tests.

Add the following composer script to the `composer.json` file:

```json
{
  "scripts": {
    "test": "vendor/bin/codecept run wpunit"
  }
}
```

Then you can just run:

```bash
composer test
```
