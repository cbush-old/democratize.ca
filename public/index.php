<?php

require_once("../config.php");
require_once("../base/exceptions.php");
require_once("../base/helpers.php");
require_once("../base/codes.php");
require_once("../base/db.php");
require_once("../base/get_controller_name.php");

static $control_keys = array("ctla","ctlb","ctlc","ctld");

$URI = array();

//  IMPORTANT: crunch the user data from QUERY_STRING.
foreach($_GET as $k => $v){
  $_GET[$k] = feels_good_man($v);
  if(in_array($k, $control_keys) && $_GET[$k])
    $URI[] = $_GET[$k];
}



try {

  $controller = get_controller_name($URI);
  
  $path = "../inc/{$controller}.php";
  
  if(!file_exists($path))
    throw new MISSING_CONTROLLER ($path);
  
  $Response = new StdClass();
  
  include $path;
  
  preg_match("/^(html|json|xml)$/",$_GET["format"])
    and $format = $_GET["format"]
    or $format = "html";

  include "../inc/out.{$format}.php";
  

} catch(HTTP_status $e){
  
  header($e->reason, true, $e->code);
  
} catch(MISSING_CONTROLLER $e){

  file_put_contents(
    "../var/log.serious",
    time()."\tMISSING CONTROLLER: {$e->which}\n",
    FILE_APPEND
  );
  header(" ", true, 500);

}
