<?php

namespace SonarSoftware\FreeRadius;

use League\CLImate\CLImate;
use RuntimeException;

class Mimosa
{
    private $climate;
    public function __construct()
    {
        $this->climate = new CLImate;
    }

    public function updateEap()
    {
        $this->climate->lightBlue()->inline("Enabling EAP and adding Mimosa dictionary... ");
        try {
            CommandExecutor::executeCommand("/bin/rm -f " . __DIR__ . "etc/freeradius/eap.conf");
            CommandExecutor::executeCommand("/bin/cp " . __DIR__ . "/../conf/thirdparty/mimosa/eap.conf /etc/freeradius/");

            CommandExecutor::executeCommand("/bin/cp " . __DIR__ . "/../conf/thirdparty/mimosa/*.pem /etc/freeradius/certs/");
            CommandExecutor::executeCommand("(cd /etc/freeradius/certs; /usr/bin/c_rehash .)");

            CommandExecutor::executeCommand("/bin/cp " . __DIR__ . "/../conf/thirdparty/mimosa/dictionary.mimosa /etc/freeradius/");
            CommandExecutor::executeCommand("/bin/cp " . __DIR__ . "/../conf/thirdparty/mimosa/dictionary /etc/freeradius/");

            //Copy this over again due to the EAP changes that may be missing
            CommandExecutor::executeCommand("/bin/cp " . __DIR__ . "/../conf/default /etc/freeradius/sites-available/");
            CommandExecutor::executeCommand("/bin/cp " . __DIR__ . "/../conf/inner-tunnel /etc/freeradius/sites-available/");

            CommandExecutor::executeCommand("/usr/sbin/service freeradius restart");
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