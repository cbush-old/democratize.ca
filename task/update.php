!#/usr/env php
<?php

$argc > 1 or die("Usage: {$argv[0]} [command]");

require "updater.class.php";

$parl = 41;
$sess = 1;

$target = "http://www.parl.gc.ca/LegisInfo/Home.aspx?language=E&ParliamentSession={$parl}-{$sess}&Mode=1&download=xml";

$Updater = new BillImporter($target);

try {

  method_exists($Updater, $cmd);

  $Updater->import_bills();

} catch(Exception $e) {

  echo $e->getMessage()."<br/>";
  
}
