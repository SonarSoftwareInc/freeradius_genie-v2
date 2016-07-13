<?php

namespace SonarSoftware\FreeRadius;

use League\CLImate\CLImate;

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
}