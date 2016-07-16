<?php

namespace SonarSoftware\FreeRadius;

use League\CLImate\CLImate;
use RuntimeException;

class FreeRadiusSetup
{
    private $climate;
    public function __construct()
    {
        $this->climate = new CLImate;
    }

    /**
     * Configure the FreeRADIUS configuration files
     */
    public function configureFreeRadiusToUseSql()
    {
        $mysqlPassword = getenv("MYSQL_PASSWORD");

        $this->climate->lightBlue()->inline("Configuring FreeRADIUS to use the SQL database... ");
        try {
            CommandExecutor::executeCommand("/bin/cp " . __DIR__ . "/../conf/radiusd.conf /etc/freeradius/");
            CommandExecutor::executeCommand("/bin/cp " . __DIR__ . "/../conf/sql.conf /etc/freeradius/");
            CommandExecutor::executeCommand("/bin/cp " . __DIR__ . "/../conf/default /etc/freeradius/sites-available/");
            CommandExecutor::executeCommand("/bin/sed -i 's/password = \"radpass\"/password = \"$mysqlPassword\"/g' /etc/freeradius/sql.conf");
            CommandExecutor::executeCommand("/usr/sbin/service freeradius restart");
        }
        catch (RuntimeException $e)
        {
            $this->climate->shout("FAILED!");
            $this->climate->shout($e->getMessage());
            $this->climate->shout("See /tmp/_genie_output for failure details.");
        }

        $this->climate->info("SUCCESS!");
    }
}