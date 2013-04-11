<?php

//  This function returns an array of regexes for matching 
//  a bill request in a URI.

//  Edit this function to add new functionality to the site.

function bill_uri_regexes(){

  static $rx = array(
    "bill_number" => "(?:(c|s|u|t)(?:-?([0-9]{1,5}))?)",
    "parl_sess" => "(?:([0-9]{1,3})(?:-([0-9]+))?)",
    "parl_id" => "([0-9]{7,9})",
    "party" => "(cpc|lpc|ndp|bq|gp|pc)",
    "ok_base" => "(latest|popular|unpopular|active|featured|mp)"
  );
  
  return $rx;

}

function request_method(){
  static $method = null;
  if(!$method) 
    return $method = $_SERVER["REQUEST_METHOD"];
  return $method;
}

function feels_good_man($in){
  return preg_replace("/[^a-z0-9-,]/","",
    strtolower(
      str_replace(" ","-",
        iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $in)
      )
    )
  );
}

function active_lang_array(){
  return array("en","fr");
}

function url_from_uri($v){

  return URL.$v;

}

function notify_bad_arg($key, $value, $message = ""){
  
  echo "Ignored bad argument '{$key}' ('{$value}') - {$message}<br/>";

  return false;

}


