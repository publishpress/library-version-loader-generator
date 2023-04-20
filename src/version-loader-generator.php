<?php

echo "Generating version manager files...\n";

// Get current version from composer.json
$composerJson = file_get_contents(__DIR__ . '/../composer.json');
$composerData = json_decode($composerJson, true);
$version = $composerData['version'];
$name = $composerData['name'];

// Convert the $name to a valid PHP namespace using camel case
$namespace = str_replace('-', '', ucwords($name, '-/\\'));
$namespace = str_replace('/', '\\', $namespace);
$namespace = str_replace('Publishpress', 'PublishPress', $namespace);

$versionStrForFunction = str_replace('.', 'Dot', $version);

$classTest = $composerData['extra']['generator']['lib-class-test'];

$actionPrefix = preg_replace_callback(
    '/([A-Z])/',
    function ($matches) {
        return '_' . strtolower($matches[1]);
    },
    $namespace
);
$actionPrefix = str_replace('\\', '', $actionPrefix);
$actionPrefix = str_replace('publish_press', 'publishpress', $actionPrefix);
$actionPrefix = ltrim($actionPrefix, '_');

function generateIncludeFile($namespace, $version, $versionStrForFunction, $classTest, $actionPrefix)
{
    $includeFile = __DIR__ . '/../src/include.php';

    if (file_exists($includeFile)) {
        unlink($includeFile);
    }

    echo "Generating file src/include.php\n";

    // Create the include.php file
    $template = <<<TEMPLATE
/*****************************************************************
 * This file is generated on composer update command by
 * a custom script. 
 * 
 * Do not edit it manually!
 ****************************************************************/

namespace %NAMESPACE%;

if (! function_exists(__NAMESPACE__ . '\\%VERSION_STR%')) {
    if (! class_exists('%NAMESPACE%\\Versions')) {
        require_once __DIR__ . '/Versions.php';

        add_action('plugins_loaded', [Versions::class, 'initializeLatestVersion'], 1, 0);
    }

    add_action('plugins_loaded', __NAMESPACE__ . '\\register%VERSION_STR%', 1, 0);

    function register%VERSION_STR%()
    {
        if (! %CLASS_TEST%) {
            \$versions = Versions::getInstance();
            \$versions->register('%VERSION%', __NAMESPACE__ . '\\initialize%VERSION_STR%');
        }
    }

    function initialize%VERSION_STR%()
    {
        require_once __DIR__ . '/../lib/autoload.php';
        do_action('%ACTION_PREFIX%_%VERSION_STR%_initialized');
    }
}

TEMPLATE;

    $placeholders = [
        '%NAMESPACE%' => $namespace,
        '%VERSION%' => $version,
        '%VERSION_STR%' => $versionStrForFunction,
        '%CLASS_TEST%' => $classTest,
        '%ACTION_PREFIX%' => $actionPrefix,
    ];
    $fileContent = "<?php\n\n" . str_replace(array_keys($placeholders), array_values($placeholders), $template);

    file_put_contents(__DIR__ . '/../src/include.php', $fileContent);
}

function generateVersionClassFile($namespace, $version, $versionStrForFunction)
{
    $versionClassFile = __DIR__ . '/../src/Versions.php';

    if (file_exists($versionClassFile)) {
        unlink($versionClassFile);
    }

    echo "Generating file src/Versions.php\n";

    $namespaceStr = str_replace('\\', '\\\\', $namespace);

    // Create the Versions.php file
    $template = <<<TEMPLATE

/*****************************************************************
 * This file is generated on composer update command by
 * a custom script. 
 * 
 * Do not edit it manually!
 ****************************************************************/
 
namespace %NAMESPACE%;

if (! class_exists('%NAMESPACE_STR%\\\\Versions')) {
    /**
     * Based on the ActionScheduler_Versions class from Action Scheduler library.
     */
    class Versions
    {
        /**
         * @var Versions
         */
        private static \$instance = null;

        private \$versions = array();

        public function register(\$versionString, \$initializationCallback): bool
        {
            if (isset(\$this->versions[\$versionString])) {
                return false;
            }

            \$this->versions[\$versionString] = \$initializationCallback;

            return true;
        }

        public function getVersions(): array
        {
            return \$this->versions;
        }

        public function latestVersion()
        {
            \$keys = array_keys(\$this->versions);
            if (empty(\$keys)) {
                return false;
            }
            uasort(\$keys, 'version_compare');
            return end(\$keys);
        }

        public function latestVersionCallback()
        {
            \$latest = \$this->latestVersion();
            if (empty(\$latest) || ! isset(\$this->versions[\$latest])) {
                return '__return_null';
            }

            return \$this->versions[\$latest];
        }

        /**
         * @return Versions
         * @codeCoverageIgnore
         */
        public static function getInstance(): ?Versions
        {
            if (empty(self::\$instance)) {
                self::\$instance = new self();
            }

            return self::\$instance;
        }

        /**
         * @codeCoverageIgnore
         */
        public static function initializeLatestVersion(): void
        {
            \$self = self::getInstance();

            call_user_func(\$self->latestVersionCallback());
        }
    }
}

TEMPLATE;

    $placeholders = [
        '%NAMESPACE%' => $namespace,
        '%NAMESPACE_STR%' => $namespaceStr,
        '%VERSION%' => $version,
        '%VERSION_STR%' => $versionStrForFunction,
    ];
    $fileContent = "<?php\n\n" . str_replace(array_keys($placeholders), array_values($placeholders), $template);

    file_put_contents(__DIR__ . '/../src/Versions.php', $fileContent);

}

function generateTestFile($namespace, $version, $versionStrForFunction, $classTest, $actionPrefix)
{
    $testFile = __DIR__ . '/../tests/VersionManagerTest.php';

    if (file_exists($testFile)) {
        unlink($testFile);
    }

    echo "Generating file tests/VersionManagerTest.php\n";

    $namespaceStr = str_replace('\\', '\\\\', $namespace);

    // Create the Versions.php file
    $template = <<<TEMPLATE
/*****************************************************************
 * This file is generated on composer update command by
 * a custom script. 
 * 
 * Do not edit it manually!
 ****************************************************************/

use %NAMESPACE%\Versions;

class VersionsCest
{
    public function testAllVersionsAreRegistered(WpunitTester \$I)
    {
        \$versions = Versions::getInstance();

        \$registeredVersions = \$versions->getVersions();

        \$I->assertNotEmpty(\$registeredVersions);
        \$I->assertEquals([
            '2.0.0.1' => '%NAMESPACE%\initialize2Dot0Dot0Dot1',
            '2.0.0.2' => '%NAMESPACE%\initialize2Dot0Dot0Dot2',
            '%VERSION%' => '%NAMESPACE%\initialize%VERSION_STR%',
        ], \$registeredVersions);
    }

    public function testLatestVersionIsTheCurrentVersion(WpunitTester \$I)
    {
        \$versions = Versions::getInstance();

        \$latestVersion = \$versions->latestVersion();

        \$I->assertEquals('%VERSION%', \$latestVersion);
    }

    public function testLatestVersionCallbackIsTheLastOne(WpunitTester \$I)
    {
        \$versions = Versions::getInstance();

        \$latestVersionCallback = \$versions->latestVersionCallback();

        \$I->assertEquals('%NAMESPACE%\\initialize%VERSION_STR%', \$latestVersionCallback);
    }

    public function testInitializeLatestVersion(WpunitTester \$I)
    {
        \$versions = Versions::getInstance();

        \$versions->initializeLatestVersion();

        \$I->assertTrue(%CLASS_TEST%);

        \$didAction = (bool)did_action('%ACTION_PREFIX%_%VERSION_STR%_initialized');
        \$I->assertTrue(\$didAction);
    }
}
        
TEMPLATE;

    $placeholders = [
        '%NAMESPACE%' => $namespace,
        '%NAMESPACE_STR%' => $namespaceStr,
        '%VERSION%' => $version,
        '%VERSION_STR%' => $versionStrForFunction,
        '%CLASS_TEST%' => $classTest,
        '%ACTION_PREFIX%' => $actionPrefix,
    ];
    $fileContent = "<?php\n\n" . str_replace(array_keys($placeholders), array_values($placeholders), $template);

    file_put_contents(__DIR__ . '/../tests/wpunit/VersionsCest.php', $fileContent);
}


generateIncludeFile($namespace, $version, $versionStrForFunction, $classTest, $actionPrefix);
generateVersionClassFile($namespace, $version, $versionStrForFunction);
generateTestFile($namespace, $version, $versionStrForFunction, $classTest, $actionPrefix);

echo "Versions file generated successfully\n";
