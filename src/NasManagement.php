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
        while ($ipAddress == null || filter_var($ipAddress, FILTER_VALIDATE_IP) === false)
        {
            $ipAddress = $input->prompt();
            if ($ipAddress == null)
            {
                $this->climate->shout("You must input an IP address.");
            }
            elseif (filter_var($ipAddress, FILTER_VALIDATE_IP) === false)
            {
                $this->climate->shout("That IP address is not valid.");
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
            CommandExecutor::executeCommand("/usr/sbin/service freeradius restart");
        }
        catch (RuntimeException $e)
        {
            $this->climate->shout("Failed to restart FreeRADIUS: {$e->getMessage()}");
        }
    }

    /**
     * List all the NAS
     */
    public function listNas()
    {
        $sth = $this->dbh->prepare("SELECT id, nasname, shortname FROM nas ORDER BY id ASC");
        $sth->execute();
        $i = 1;
        foreach ($sth->fetchAll() as $record)
        {
            $this->climate->bold()->lightBlue("$i. {$record['nasname']} ({$record['shortname']})");
            $i++;
        }
    }

    /**
     * Delete a NAS
     */
    public function deleteNas()
    {
        $input = $this->climate->lightBlue()->input("What is the IP address of the NAS you want to remove?");
        $ipAddress = null;
        while ($ipAddress == null || filter_var($ipAddress, FILTER_VALIDATE_IP) === false)
        {
            $ipAddress = $input->prompt();
            if ($ipAddress == null)
            {
                $this->climate->shout("You must input an IP address.");
            }
            elseif (filter_var($ipAddress, FILTER_VALIDATE_IP) === false)
            {
                $this->climate->shout("That IP address is not valid.");
            }
        }

        $sth = $this->dbh->prepare("DELETE from nas WHERE nasname=?");
        if ($sth->execute([$ipAddress]))
        {
            $this->climate->shout("NAS was deleted!");
            try {
                CommandExecutor::executeCommand("/usr/sbin/service freeradius restart");
            }
            catch (RuntimeException $e)
            {
                $this->climate->shout("Failed to restart FreeRADIUS!");
            }
        }
        else
        {
            $this->climate->shout("Failed to delete the NAS from the database. Maybe the IP wasn't found? Try using the List NAS entries function first.");
        }
    }
}