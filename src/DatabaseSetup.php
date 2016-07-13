<?php

namespace SonarSoftware\FreeRadius;

use League\CLImate\CLImate;
use RuntimeException;

class DatabaseSetup
{
    private $dbh;
    private $climate;
    public function __construct()
    {
        $this->dbh = new \PDO('mysql:host=localhost', 'root', getenv('MYSQL_PASSWORD'));
        $this->climate = new CLImate;
    }

    /**
     * Create the initial database.
     */
    public function createInitialDatabase()
    {
        $this->climate->lightBlue()->inline("Creating initial database... ");
        $this->dbh->exec("CREATE DATABASE radius;");
        exec("/usr/bin/mysql -uroot -p" . escapeshellarg(getenv("MYSQL_PASSWORD")) . " radius < /etc/freeradius/sql/mysql/schema.sql");
        exec("/usr/bin/mysql -uroot -p" . escapeshellarg(getenv("MYSQL_PASSWORD")) . " radius < /etc/freeradius/sql/mysql/nas.sql");
        $this->climate->info("SUCCESS!");
    }

    /**
     * Comment out bind-address so it will listen remotely
     */
    public function enableRemoteAccess()
    {
        $this->climate->lightBlue()->inline("Enabling remote access... ");
        try {
            Installer::executeCommand("/bin/sed -i 's/^bind-address/#bind-address/g' /etc/mysql/mariadb.conf.d/50-server.cnf");
            Installer::executeCommand("/usr/sbin/service mysql restart");
        }
        catch (RuntimeException $e)
        {
            $this->climate->shout("FAILED!");
            $this->climate->shout($e->getMessage());
            $this->climate->shout("See /tmp/_genie_output for failure details.");
            return;
        }
        $this->climate->info("SUCCESS!");
    }

    /**
     * Disable remote access
     */
    public function disableRemoteAccess()
    {
        $this->climate->lightBlue()->inline("Disabling remote access... ");
        try {
            Installer::executeCommand("/bin/sed -i 's/^#bind-address/bind-address/g' /etc/mysql/mariadb.conf.d/50-server.cnf");
            Installer::executeCommand("/usr/sbin/service mysql restart");
        }
        catch (RuntimeException $e)
        {
            $this->climate->shout("FAILED!");
            $this->climate->shout($e->getMessage());
            $this->climate->shout("See /tmp/_genie_output for failure details.");
            return;
        }
        $this->climate->info("SUCCESS!");
    }
}