<?php
function strextract($str, $start="", $end="", $r=0){
  
  if($start){
    if (($a=strpos($str,$start))===false){ return false; }
  } else 
    $start=$a="";
    
  if($end){
    if(!$r&&($b=strrpos($str,$end))<$a) return false;
    if($r&&($b=strpos($str,$end))<$a) return false;
    return substr($str,(int)$a+=strlen($start),$b-$a);
  }
  
  return substr($str,(int)$a+=strlen($start));
  
}

function get_title($str){
  
  if(strlen($str)>0){
    preg_match("/\<title\>(.*)\<\/title\>/",$str,$title);
    return isset($title[1])?$title[1]:0;
  }
  
}
