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


