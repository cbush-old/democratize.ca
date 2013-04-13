<?php
//  public/api/ index
//  handle requests

require_once("../../config.php");
require_once("../../base/exceptions.php");
require_once("../../base/helpers.php");
require_once("../../base/codes.php");
require_once("../../base/db.php");

require("../../base/request.php");
require("../../request/bill.php");
require("../../request/mp.php");
require("../../request/comment.php");
require("../../request/help.php");
require("../../request/vote.php");
require("../../request/user.php");


function get_request($cmd, $args){

  static $action = array(
    'bill' => 'Bill_request',
    'mp' => 'Mp_request',
    'comment' => 'Comment_request',
    'help' => 'Help_request',
    'vote' => 'Vote_request',
    'user' => 'User_request'
  );

  if(!isset($action[$cmd])) 
    throw new HTTP_status(400);
  
  if(!class_exists($action[$cmd]))
    throw new HTTP_status(500);
  
  return new $action[$cmd] (request_method(), $args);
  
}


//  crunch the user data from QUERY_STRING:

foreach($_GET as $k => $v)
  $_GET[$k] = feels_good_man($v);
  

try {

  if(!isset($_GET["uri"])) 
    $_GET["uri"] = "help";

  $args = explode("/", $_GET["uri"]);
  
  $cmd = array_shift($args);
  
  $request = get_request($cmd, $args);
  
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
  
