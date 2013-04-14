<?php

$data = json_encode($Response);

if(isset($_GET["callback"])){
  
  $callback = preg_replace("/[^0-9A-z_-]/","",$_GET["callback"]);
  $data = "{$callback}({$data});";

  header("Content-type:application/javascript");


} else {

  header("Content-type:application/json");

}

echo $data;
