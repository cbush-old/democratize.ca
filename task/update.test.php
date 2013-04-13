#!/usr/bin/env php
<?php

$argc > 1 or die("Usage: {$argv[0]} [command]\n");

$cmd = $argv[1];

require "updater.class.php";

$parl = 41;
$sess = 1;

$target = "http://www.parl.gc.ca/LegisInfo/Home.aspx?language=E&ParliamentSession={$parl}-{$sess}&Mode=1&download=xml";

$Updater = new BillImporter($target);



try {


  if(!method_exists($Updater, $cmd))
    throw new Exception("Unknown command {$cmd}");

  var_dump($Updater->$cmd());


} catch(Exception $e) {

  echo $e->getMessage()."\n";
  
}
