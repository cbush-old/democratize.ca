<?php
require_once("../config.php");
require_once("../base/helpers.php");
require_once("../base/db.php");

$possible_bases = array(
  "about"=>1,
  "bills"=>1,
  "comments"=>1,
  "contribute"=>1,
  "license"=>1,
  "mps"=>1,
  "parties"=>1,
  "privacy"=>1,
  "ridings"=>1,
  "rss"=>1,
  "subjects"=>1,
  "users"=>1,
  "votes"=>1
);

$base = getclean("ctla");

$Response = new StdClass();

if(!$base){

  include ("../inc/get.home.php");

} else if(isset($possible_bases[$base])){

  include ("../inc/get.{$base}.php");

} else {

  include ("../inc/get.notfound.php");

}

$format = $_GET["format"] or $format = "html";

include("../inc/out.{$format}.php");
