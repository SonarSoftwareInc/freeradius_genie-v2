<?php

namespace SonarSoftware\FreeRadius;

use League\CLImate\CLImate;
use PDO;
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
        $sth = $this->dbh->prepare("GRANT ALL PRIVILEGES ON radius.* TO 'radius'@'127.0.0.1' IDENTIFIED BY ?");
        $sth->execute([getenv('MYSQL_PASSWORD')]);
        $this->dbh->exec("FLUSH PRIVILEGES");
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
            if (file_exists("/etc/mysql/mariadb.conf.d/50-server.cnf"))
            {
                CommandExecutor::executeCommand("/bin/sed -i 's/^bind-address/#bind-address/g' /etc/mysql/mariadb.conf.d/50-server.cnf");
                CommandExecutor::executeCommand("/usr/sbin/service mysql restart");
            }
            elseif (file_exists("/etc/mysql/mysql.conf.d/mysqld.cnf"))
            {
                CommandExecutor::executeCommand("/bin/sed -i 's/^bind-address/#bind-address/g' /etc/mysql/mysql.conf.d/mysqld.cnf");
                CommandExecutor::executeCommand("/usr/sbin/service mysql restart");
            }
            elseif (file_exists("/etc/mysql/my.cnf"))
            {
                CommandExecutor::executeCommand("/bin/sed -i 's/^bind-address/#bind-address/g' /etc/mysql/my.cnf");
                CommandExecutor::executeCommand("/usr/sbin/service mysql restart");
            }
            else
            {
                $this->climate->shout("Can't find the MySQL configuration file - this is probably an unsupported version of Ubuntu. Please notify support@sonar.software and we'll get it fixed!");
                return;
            }

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
            if (file_exists("/etc/mysql/mariadb.conf.d/50-server.cnf"))
            {
                CommandExecutor::executeCommand("/bin/sed -i 's/^#bind-address/bind-address/g' /etc/mysql/mariadb.conf.d/50-server.cnf");
                CommandExecutor::executeCommand("/usr/sbin/service mysql restart");
            }
            elseif (file_exists("/etc/mysql/mysql.conf.d/mysqld.cnf"))
            {
                CommandExecutor::executeCommand("/bin/sed -i 's/^#bind-address/bind-address/g' /etc/mysql/mysql.conf.d/mysqld.cnf");
                CommandExecutor::executeCommand("/usr/sbin/service mysql restart");
            }
            elseif (file_exists("/etc/mysql/my.cnf"))
            {
                CommandExecutor::executeCommand("/bin/sed -i 's/^#bind-address/bind-address/g' /etc/mysql/my.cnf");
                CommandExecutor::executeCommand("/usr/sbin/service mysql restart");
            }
            else
            {
                $this->climate->shout("Can't find the MySQL configuration file - this is probably an unsupported version of Ubuntu. Please notify support@sonar.software and we'll get it fixed!");
                return;
            }
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
     * Add a remote access user
     */
    public function addRemoteAccessUser()
    {
        $input = $this->climate->lightBlue()->input("What is the IP address of the remote server that will be accessing the database?");
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

        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $username = '';
        for ($i = 0; $i < 16; $i++) {
            $username .= $characters[rand(0, strlen($characters) - 1)];
        }

        $password = '';
        for ($i = 0; $i < 16; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }

        $sth = $this->dbh->prepare("GRANT ALL ON radius.* TO ?@? IDENTIFIED BY ?");
        if ($sth->execute([$username, $ipAddress, $password]))
        {
            $sth = $this->dbh->prepare("FLUSH PRIVILEGES");
            $sth->execute();
            $this->climate->lightMagenta("Added a user with the username $username and the password $password. Copy this username and password, you'll need it!");
        }
        else
        {
            $this->climate->shout("Failed to create the user!");
        }
    }

    /**
     * List all the remote access users
     */
    public function listRemoteAccessUsers()
    {
        $sth = $this->dbh->prepare("SELECT Host, User FROM mysql.user WHERE Host != 'localhost' AND Host != '127.0.0.1' AND Host != '::1'");
        $sth->execute();
        foreach ($sth->fetchAll(PDO::FETCH_ASSOC) as $result)
        {
            $this->climate->lightBlue("{$result['User']} can be used from {$result['Host']}");
        }
    }

    /**
     * Delete a remote access user
     */
    public function deleteRemoteAccessUser()
    {
        $input = $this->climate->lightBlue()->input("What is the username you want to remove?");
        $username = null;
        while ($username == null)
        {
            $username = $input->prompt();
            if ($username == null)
            {
                $this->climate->shout("You must input a username.");
            }
        }

        $sth = $this->dbh->prepare("SELECT Host, User FROM mysql.user WHERE Host != 'localhost' AND Host != '127.0.0.1' AND User=?");
        if ($sth->execute([$username]))
        {
            $result = $sth->fetch(PDO::FETCH_ASSOC);
            $host = $result['Host'];

            $sth = $this->dbh->prepare("DELETE FROM mysql.user WHERE Host=? AND User=?");
            if ($sth->execute([$host, $username]))
            {
                $sth = $this->dbh->prepare("FLUSH PRIVILEGES");
                $sth->execute();
                $this->climate->info("User removed!");
            }
            else
            {
                $this->climate->shout("Failed to remove user!");
            }
            return;
        }
        else
        {
            $this->climate->shout("User not found, or is not a remote user. Check the username and try again.");
        }
    }
}
