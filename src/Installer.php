<?php

namespace SonarSoftware\FreeRadius;

use League\CLImate\CLImate;
use RuntimeException;

class Installer
{
    private $climate;
    public function __construct()
    {
        $this->fileDirectory = "'" . dirname(__FILE__) . "/..'";
        $this->climate = new CLImate;
    }

    /**
     * Install function
     */
    public function preliminaryInstall()
    {
        if (trim(shell_exec("whoami")) != "root")
        {
            $this->climate->shout("Please run this script as root.");
            return;
        }

        $this->climate->bold()->info("Beginning FreeRADIUS installation!");
        try {
            $this->installDatabase();
            $this->installFreeRadius();
        }
        catch (RuntimeException $e)
        {
            $this->climate->shout("FAILED!");
            $this->climate->shout($e->getMessage());
            $this->climate->shout("See /tmp/_genie_output for failure details.");
            return;
        }

        $this->climate->bold()->info("Installation complete.");
        $this->climate->bold()->shout("You must configure your database before proceeding - run /usr/bin/mysql_secure_installation to begin.");
        $this->climate->lightBlue("Refer to the documentation at https://github.com/SonarSoftware/freeradius_genie if you need help!");
        $this->climate->bold()->info("Once your database is configured, run 'php genie' to access the simple Sonar FreeRADIUS configuration tool!");
    }

    /**
     * Install FreeRADIUS
     */
    private function installFreeRadius()
    {
        $this->climate->info()->inline("Installing FreeRADIUS and tools... ");
        $this->executeCommand("apt-get -y install freeradius freeradius-common freeradius-utils freeradius-mysql");
        $this->climate->info("SUCCESS!");
    }

    /**
     * Install MariaDB server/client
     */
    private function installDatabase()
    {
        $this->climate->info()->inline("Installing MariaDB and tools... ");
        $this->executeCommand("apt-get -y install mariadb-server mariadb-client");
        $this->climate->info("SUCCESS!");
    }

    /**
     * @param $command
     * @param bool $withoutException
     */
    public static function executeCommand($command, $withoutException = false)
    {
        exec($command . " 1> /dev/null 2> /tmp/_genie_output", $output, $returnVar);
        if ($returnVar !== 0 && $withoutException === false)
        {
            throw new RuntimeException(implode(",",$output));
        }
        elseif ($returnVar !== 0)
        {
            $climate = new CLImate;
            $climate->shout("FAILED!");
            $climate->shout(implode(",",$output));
            $climate->shout("See /tmp/_genie_output for failure details.");
        }
    }
}