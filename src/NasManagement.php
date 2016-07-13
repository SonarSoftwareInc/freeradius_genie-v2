<?php

namespace SonarSoftware\FreeRadius;

use League\CLImate\CLImate;
use PDO;
use RuntimeException;

class NasManagement
{
    private $dbh;
    private $climate;
    public function __construct()
    {
        $this->dbh = new \PDO('mysql:dbname=radius;host=localhost', 'root', getenv('MYSQL_PASSWORD'));
        $this->climate = new CLImate;
    }

    /**
     * Add a new NAS to the database
     */
    public function addNas()
    {
        $input = $this->climate->lightBlue()->input("What is the NAS IP address?");
        $ipAddress = null;
        while ($ipAddress == null)
        {
            $ipAddress = $input->prompt();
            if ($ipAddress == null)
            {
                $this->climate->shout("You must input an IP address.");
            }
        }

        $sth = $this->dbh->prepare("SELECT shortname FROM nas WHERE nasname=?");
        $sth->execute([$ipAddress]);
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        if ($result !== false)
        {
            $this->climate->shout("There is already a NAS with that IP named {$result['shortname']}. Please enter a different IP, or remove this NAS first.");
            $this->addNas();
        }

        $input = $this->climate->lightBlue()->input("What is a short name for this NAS?");
        $name = null;
        while ($name == null)
        {
            $name = $input->prompt();
            if ($name == null)
            {
                $this->climate->shout("You must input a short name.");
            }
        }

        $name = preg_replace("/[^A-Za-z0-9]/", "", $name);

        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $characters[rand(0, strlen($characters) - 1)];
        }

        $sth = $this->dbh->prepare("INSERT INTO nas (nasname, shortname, type, secret, description) VALUES(?,?,?,?,?)");
        if ($sth->execute([$ipAddress, substr($name,0,255), 'other', $secret, 'Added via the Sonar FreeRADIUS Genie tool']))
        {
            $this->climate->bold()->magenta("Added the NAS with a random secret of $secret - record this secret, you will need it shortly!");
        }
        else
        {
            $this->climate->shout("Failed to add the NAS to the database.");
            return;
        }

        try {
            Installer::executeCommand("/usr/sbin/service freeradius restart");
        }
        catch (RuntimeException $e)
        {
            $this->shout("Failed to restart FreeRADIUS: {$e->getMessage()}");
        }
    }
}