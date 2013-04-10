<?php
function feels_good_man($in){
  return preg_replace("/[^a-z0-9-,]/","",
    strtolower(
      str_replace(" ","-",
        iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $in)
      )
    )
  );
}

function url_from_uri($v){

  return URL.$v;

}

function notify_bad_arg($key, $value, $message = ""){
  
  echo "Ignored bad argument '{$key}' ('{$value}') - {$message}<br/>";

  return false;

}


