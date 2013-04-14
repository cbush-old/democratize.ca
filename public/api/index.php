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
  'mps' => 'Mps_request',
  'subjects' => 'Subjects_request',
  'summary' => 'Summary_request',
  'user' => 'User_request',
  'vote' => 'Vote_request',
);

static $alias = array(
  "active" => "bill active",
  "newest" => "bill newest",
  "oldest" => "bill oldest",
  "popular" => "bill popular",
  "unpopular" => "bill unpopular",
  "mp" => "bill mp",
  "subject" => "bill subject",
);

//  load the request classes which handle the actions

foreach(array_keys($action) as $a)
  require("../../request/{$a}.php");



//  crunch the user data from QUERY_STRING:

foreach($_GET as $k => $v)
  $_GET[$k] = feels_good_man($v);
  

if(!isset($_GET["format"])) $_GET["format"] = "html";


class Help_request extends Request {
  
  public function GET($args){
    
    global $action;
    
    $this->response->commands = implode(", ", array_keys($action));
  
  }

}

$action['help'] = 'Help_request';



try {


  if(!isset($_GET["uri"])) 
    $_GET["uri"] = "help";

    
  $args = explode("/", preg_replace("/[\/]+$/","",$_GET["uri"]));
  
  $cmd = array_shift($args);

  if(isset($alias[$cmd])){
    $args = array_merge(explode(" ", $alias[$cmd]), $args);
    $cmd = array_shift($args);
  }

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


  
