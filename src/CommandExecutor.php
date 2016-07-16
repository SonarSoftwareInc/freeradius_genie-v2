<?php

namespace SonarSoftware\FreeRadius;

use League\CLImate\CLImate;
use RuntimeException;

class CommandExecutor
{
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