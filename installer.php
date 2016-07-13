<?php

use SonarSoftware\FreeRadius\Installer;

require("vendor/autoload.php");

$installer = new Installer();
$installer->preliminaryInstall();