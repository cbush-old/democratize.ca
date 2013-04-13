<?php

function request_method(){
  static $method = null;
  static $OK = array(
    "GET"=>true,"POST"=>true,"PUT"=>true,"DELETE"=>true,
    "OPTIONS"=>true,"HEAD"=>true,"TRACE"=>true,"CONNECT"=>true
  );
  if(!$method){
    $method = $_SERVER["REQUEST_METHOD"];
    if(!isset($OK[$method]))
      throw new HTTP_status (402, "Method not recognized");
  }
  return $method;
}

function feels_good_man($in){
  return preg_replace("/[^a-z0-9-,\/]/","",
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

  return BASE_URL.$v;

}

function notify_bad_arg($key, $value, $message = ""){
  
  echo "Ignored bad argument '{$key}' ('{$value}') - {$message}<br/>";

  return false;

}



function get_bill_from_uri_string($argstr){

  $bill = new StdClass;
  $bill->chamber = "C"; // some defaults
  $bill->number = ""; 
  $bill->parliament = "41";
  $bill->session = "1";

  if(preg_match("/([0-9]{2})-?([0-9])?/", $argstr, $m)){
  
    if(isset($m[1])) $bill->parliament = $m[1];
    if(isset($m[2])) $bill->session = $m[2];

  }
  
  if(preg_match("/(c|t|u|s)-?([0-9]{1,5})/", $argstr, $m)){
    
    if(isset($m[1])) $bill->chamber = strtoupper($m[1]);
    if(isset($m[2])) $bill->number = $m[2];
  
  }
  
  return $bill;

}
