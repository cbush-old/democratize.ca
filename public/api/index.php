<?php
//  public/api/ index
//  handle requests

require_once("../../config.php");
require_once("../../base/exceptions.php");
require_once("../../base/helpers.php");
require_once("../../base/codes.php");
require_once("../../base/db.php");

require_once("../../base/request.php");

static $action = array(
  'bill' => 'Bill_request',
  'comment' => 'Comment_request',
  'help' => 'Help_request',
  'mp' => 'Mp_request',
  'summary' => 'Summary_request',
  'user' => 'User_request',
  'vote' => 'Vote_request',
);

//  load the request classes which handle the actions

foreach(array_keys($action) as $a)
  require("../../request/{$a}.php");



//  crunch the user data from QUERY_STRING:

foreach($_GET as $k => $v)
  $_GET[$k] = feels_good_man($v);
  



try {


  if(!isset($_GET["uri"])) 
    $_GET["uri"] = "help";

    
  $args = explode("/", $_GET["uri"]);
  
  $cmd = array_shift($args);

  if(!isset($action[$cmd])) 
    throw new HTTP_status(400);
  
  if(!class_exists($action[$cmd])) 
    throw new HTTP_status(500);
  
  
  $method = request_method();
  
  $request = new $action[$cmd] ($method, $args);

  $Response = $request->get_response();


  preg_match("/^(html|json|xml)$/",$_GET["format"])
    and $format = $_GET["format"]
    or $format = "html";

  require "../../inc/out.{$format}.php";


} catch(HTTP_status $e){
  
  $_GET = array($e->code => $e->reason);
  
  require "../error.php";
  
}

// file_get_contents('php://input');
  
