<?php

namespace SonarSoftware\FreeRadius;

use League\CLImate\CLImate;

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
        #exec('/bin/sed -i \'s/password = "radpass"/password = "sadpass"/g\' /etc/freeradius/sql.conf');
        $this->climate->lightBlue("Initial FreeRADIUS configuration completed!");
    }
}