<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Unit extends \Codeception\Module
{
    public function runShellCommand($command)
    {
        return shell_exec($command);
    }

    public function makeDir($dir, $permissions = 0777, $recursive = true)
    {
        return mkdir($dir, $permissions, $recursive);
    }
}
