<?php
require_once("../config.php");
require_once("../base/helpers.php");
require_once("../base/codes.php");
require_once("../base/db.php");

//  IMPORTANT: crunch the user data from GET.
foreach($_GET as $k => $v)
  $_GET[$k] = feels_good_man($v);


//  If first segment of URI is not in these, serve notfound page. 
$possible_bases = array(
  "about"=>1,  "bills"=>1,  "comments"=>1,
  "contribute"=>1,  "license"=>1,  "mps"=>1,
  "parties"=>1,  "privacy"=>1,  "ridings"=>1,
  "rss"=>1,  "subjects"=>1,  "users"=>1,  
  "votes"=>1
);

$base = $_GET["ctla"];

$Response = new StdClass();

if($base == ""){

  include ("../inc/get.home.php");

} else if(isset($possible_bases[$base])){

  include ("../inc/get.{$base}.php");

} else {

  include ("../inc/get.notfound.php");

}

preg_match("/^(html|json|xml)$/",$_GET["format"])
  and $format = $_GET["format"]
  or $format = "html";

include("../inc/out.{$format}.php");
