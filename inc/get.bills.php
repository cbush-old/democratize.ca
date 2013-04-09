<?php

$getreq = array(
  strtoupper($_GET["ctlb"]),
  strtoupper($_GET["ctlc"]),
);

// Three possibilities: parliament number, bill number or parl.gc.ca id

$request = new StdClass();
$request->chamber = "C"; // some defaults
$request->number = ""; 
$request->parliament = "41";
$request->session = "1";
$request->parl_id = "";

{ 

$f_bill = 0; 
$f_parl = 0;
$f_parl_id = 0;

foreach($getreq as $q){
  
  if(!$q) continue;

  $preg_result = array();
  
  if(!$f_bill && preg_match("/^(C|S|U)?(?:-?([0-9]+))?$/",$q,$matches)){
    
    if($matches[1]) $request->chamber = $matches[1];
    if($matches[2]) $request->number = $matches[2];
    $f_bill = 1;
    
  } else if(!$f_parl && preg_match("/^([0-9]{1,3})(?:-([0-9]+))?$/",$q,$matches)){

    if($matches[1]) $request->parliament = $matches[1];
    if($matches[2]) $request->session = $matches[2];
    $f_parl = 1;
    
  } else if(!$f_parl_id && preg_match("/^([0-9]{7,})$/",$q,$matches)){
    
    $request->parl_id = $matches[1];
    $f_parl_id = 1;
    
  }

}

}


$cond = array(
  "`parl_session`='{$request->parliament}-{$request->session}'",
  "`chamber`='{$request->chamber}'"
);

if($request->number) $cond[] = "`number`='{$request->number}'";
if($request->parl_id) $cond[] = "`parl_id`='{$request->parl_id}'";

$cond = implode("&&",$cond);

$result = DB::query(
  "select * from `bills` 
  where {$cond}
  limit 10"
);

while($r = $result->fetchObject()){
  

}




