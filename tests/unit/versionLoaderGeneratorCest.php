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

        $I->seeFileFound($this->destinationPath . '/lib/include.php');

        include $this->destinationPath . '/lib/include.php';
    }

    public function _after(UnitTester $I)
    {
//        $I->deleteDir($this->destinationPath);
    }

    public function testGenerationOfIncludeFile(UnitTester $I)
    {
        $I->assertTrue(
            defined('PUBLISHPRESS_PSR_CONTAINER_INCLUDED'),
            'Constant PUBLISHPRESS_PSR_CONTAINER_INCLUDED is not defined'
        );
        $I->assertEquals(PUBLISHPRESS_PSR_CONTAINER_INCLUDED, realpath($this->destinationPath . '/lib'));
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
                'priority' => 1,
                'accepted_args' => 0,
            ]),
            'Action plugins_loaded, for PublishPress\PsrContainer\register2Dot0Dot1Dot4 is not registered'
        );

        $I->assertTrue(
            DummyWPActionsStore::hasAction('plugins_loaded', [
                'callback' => [
                    'PublishPress\\PsrContainer\\Versions',
                    'initializeLatestVersion'
                ],
                'priority' => 1,
                'accepted_args' => 0,
            ]),
            'Action plugins_loaded, for PublishPress\PsrContainer\Versions::initializeLatestVersion is not registered'
        );
    }

    public function testRegisterRegisteringOfVersions(UnitTester $I)
    {
        call_user_func('PublishPress\\PSRContainer\\register2Dot0Dot1Dot4');

        $versions = \PublishPress\PSRContainer\Versions::getInstance();
        $I->assertEquals(
            ['2.0.1.4' => 'PublishPress\\PsrContainer\\initialize2Dot0Dot1Dot4'],
            $versions->getVersions(),
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

    public function testGenerationOfVersionsClass(UnitTester $I)
    {
        $I->seeFileFound($this->destinationPath . '/lib/Versions.php');

        $I->assertTrue(
            class_exists('PublishPress\\PsrContainer\\Versions'),
            'Class PublishPress\\PsrContainer\\Versions is not defined'
        );

        $versions = \PublishPress\PsrContainer\Versions::getInstance();

        $I->assertEquals(
            '2.0.1.4',
            $versions->latestVersion(),
            'Latest version is not correct'
        );
    }

    public function testGenerationOfClassTest(UnitTester $I)
    {
        $I->seeFileFound($this->destinationPath . '/tests/wpunit/VersionsCest.php');
    }
}
