<?php

class versionLoaderGeneratorCest
{
    private $projectPath = __DIR__ . '/../..';
    private $sourcePath = __DIR__ . '/../_data/libraries/dummy-library';
    private $destinationPath = __DIR__ . '/../_output/dummy-library';

    public function _before(UnitTester $I)
    {
        $I->deleteDir($this->destinationPath);
        $I->copyDir($this->sourcePath, $this->destinationPath);
        $I->makeDir($this->destinationPath . '/vendor/bin');
        $I->copyDir($this->projectPath . '/bin', $this->destinationPath . '/vendor/bin');

        // Run shell command calling the bin/version-manager-generator.php script
        $I->runShellCommand(
            'cd ./tests/_output/dummy-library && chmod +x ./vendor/bin/version-loader-generator && ./vendor/bin/version-loader-generator'
        );

        $I->seeFileFound($this->destinationPath . '/src/include.php');

        include $this->destinationPath . '/src/include.php';
    }

    public function testGenerationOfIncludeFile(UnitTester $I)
    {
        $I->assertTrue(
            defined('PUBLISHPRESS_PSR_CONTAINER_INCLUDED'),
            'Constant PUBLISHPRESS_PSR_CONTAINER_INCLUDED is not defined'
        );
        $I->assertEquals(PUBLISHPRESS_PSR_CONTAINER_INCLUDED, realpath($this->destinationPath . '/src'));
        $I->assertTrue(
            function_exists('PublishPress\\PSRContainer\\register2Dot0Dot1Dot4'),
            'Function PublishPress\\PSRContainer\\register2Dot0Dot1Dot4 is not defined'
        );
        $I->assertTrue(
            function_exists('PublishPress\\PSRContainer\\initialize2Dot0Dot1Dot4'),
            'Function PublishPress\\PSRContainer\\initialize2Dot0Dot1Dot4 is not defined'
        );
    }

    public function testIncludeFileRegisterActions(UnitTester $I)
    {
        $I->assertTrue(
            DummyWPActionsStore::hasAction('plugins_loaded', [
                'callback' => 'PublishPress\PsrContainer\register2Dot0Dot1Dot4',
                'priority' => -200,
                'accepted_args' => 0,
            ]),
            'Action plugins_loaded, for PublishPress\PsrContainer\register2Dot0Dot1Dot4 is not registered'
        );

        $I->assertTrue(
            DummyWPActionsStore::hasAction('plugins_loaded', [
                'callback' => [
                    'PublishPress\\PsrContainer\\VersionLoader',
                    'initializeLatestVersion'
                ],
                'priority' => -190,
                'accepted_args' => 0,
            ]),
            'Action plugins_loaded, for PublishPress\PsrContainer\VersionLoader::initializeLatestVersion is not registered'
        );
    }

    public function testRegisterRegisteringOfVersions(UnitTester $I)
    {
        call_user_func('PublishPress\\PSRContainer\\register2Dot0Dot1Dot4');

        $loader = \PublishPress\PSRContainer\VersionLoader::getInstance();
        $I->assertSame(
            $loader,
            \PublishPress\PsrContainer\Versions::getInstance(),
            'Versions alias must share the VersionLoader registry'
        );
        $I->assertEquals(
            ['2.0.1.4' => 'PublishPress\\PsrContainer\\initialize2Dot0Dot1Dot4'],
            $loader->getVersions(),
            'Version is not registered'
        );
    }

    public function testInitializingOfVersion(UnitTester $I)
    {
        call_user_func('PublishPress\\PSRContainer\\initialize2Dot0Dot1Dot4');

        $I->assertTrue(
            defined('PUBLISHPRESS_PSR_CONTAINER_VERSION'),
            'Constant PUBLISHPRESS_PSR_CONTAINER_VERSION is not defined'
        );
        $I->assertEquals('2.0.1.4', PUBLISHPRESS_PSR_CONTAINER_VERSION);

        $I->assertTrue(
            DummyWPActionsStore::didAction('publishpress_psr_container_2Dot0Dot1Dot4_initialized'),
            'Action publishpress_psr_container_2Dot0Dot1Dot4_initialized is not fired'
        );

        $I->assertTrue(
            interface_exists('PublishPress\Psr\Container\ContainerInterface'),
            'Interface PublishPress\Psr\Container\ContainerInterface is not defined'
        );
    }

    public function testGenerationOfVersionLoaderClass(UnitTester $I)
    {
        $I->seeFileFound($this->destinationPath . '/src/VersionLoader.php');

        $I->assertTrue(
            class_exists('PublishPress\\PsrContainer\\VersionLoader'),
            'Class PublishPress\\PsrContainer\\VersionLoader is not defined'
        );
        $I->assertTrue(
            class_exists('PublishPress\\PsrContainer\\Versions', false),
            'Class PublishPress\\PsrContainer\\Versions is not aliased'
        );

        $loader = \PublishPress\PsrContainer\VersionLoader::getInstance();

        $I->assertSame(
            $loader,
            \PublishPress\PsrContainer\Versions::getInstance(),
            'Versions alias must share the VersionLoader registry'
        );

        $I->assertEquals(
            '2.0.1.4',
            $loader->latestVersion(),
            'Latest version is not correct'
        );
    }

    public function testGenerationOfClassTest(UnitTester $I)
    {
        $I->seeFileFound($this->destinationPath . '/tests/codeception/Integration/VersionLoaderCest.php');
        $I->openFile($this->destinationPath . '/tests/codeception/Integration/VersionLoaderCest.php');
        $I->seeInThisFile('class VersionLoaderCest');
        $I->seeInThisFile('IntegrationTester');

        $I->dontSeeFileFound('Versions.php', $this->destinationPath . '/src');
        $I->dontSeeFileFound('VersionsCest.php', $this->destinationPath . '/tests/wpunit');
    }
}
