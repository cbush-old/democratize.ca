#!/usr/bin/env php
<?php

# This script updates the mp and alias_mp tables.
# Delete data/mpnames.txt to download a fresh copy
# (automatically replaced if older than 24 hours)

$DIR = dirname(__FILE__);

require_once $DIR."/../base/db.php";
require_once $DIR."/../base/codes.php";

$datapath = $DIR."/data/mpnames.txt";

if(!file_exists($datapath) or (time() - filemtime($datapath) > 86400)){
  
  echo "Executing dlparsemp > {$datapath}\n";
  exec($DIR."/dlparsemp > {$datapath}");
  
}

$data = trim(file_get_contents($datapath));

if(!strlen($data))
  die("Error: no data?\n");
  
echo "Successfully opened {$datapath}\n";


$data = explode("\n", $data);

$props = array("name", "riding_lcname", "party");

$mps = array();
$party = array(
  "Bl" => "bq",
  "Co" => "cpc",
  "Li" => "lpc",
  "ND" => "ndp",
  "Gr" => "gpc"
);

$alias = array();

if(!count($data)) die("Something went wrong... no data?\n");

echo "Parsing data\n";

foreach($data as $dat){

  $dat = explode(":", $dat);
  
  $man = new StdClass;
  
  for($i = 0; $i < 3; ++$i){
  
    $prop = $props[$i];
    
    $val = trim($dat[$i]);
    
    if($prop=="name"){
    
      $val = explode(", ", $val);
      $man->first_name = trim($val[1]);
      $man->last_name = trim($val[0]);
      $man->lcname = lcname("{$man->first_name} {$man->last_name}");
      $alias[] = $man->lcname;
      
    } else {
    
      if($prop=="party"){
        if(!$val)
          $val = "ind";
        else {
          $val = $party[substr($val,0,2)];
      
        }
      } else if($prop=="riding_lcname") {
        
        $val = lcname($val);
        
      }
      
      $man->$prop = $val;
  
    }
    
  }
  
  $mps[] = $man;

}




$where = array();
$values = array();

foreach($alias as $a){
  $a = DB::get(1)->quote($a);
  $where[] = "alias={$a}";
  $values[] = "({$a},{$a})";
}
$where = implode("||", $where);

try {
  
  echo "Updating alias_mp table\n";
  DB::get(1)->query("start transaction;");
  $r = DB::get(1)->query("delete from alias_mp where {$where};");
  $count = $r->rowCount();
  echo "Deleted {$count} old alias".($count==1?"":"es")."\n";
  
  $values = implode(",", $values);
  $r = DB::get(1)->query("insert into alias_mp (alias, mp_lcname) values {$values};");
  $count = $r->rowCount();
  echo "Inserted {$count} new alias".($count==1?"":"es")."\n";
  DB::get(1)->query("commit;");
  echo "Committed\n";
  
} catch(PDOException $e){
  
  var_dump($e);
  DB::get(1)->query("rollback;");
  die("Failed at updating mp_alias table\n");
  
}


$where = array();
$values = array();
$i=0;
$fields = "";


if(!count($mps)) die("Something went wrong... no mps?");

foreach($mps as $mp){
  $lcname = DB::get(1)->quote($mp->lcname);
  $where[] = "lcname={$lcname}";

  $vals = array();
  foreach($mp as $v)
    $vals[] = DB::get(1)->quote($v);
  
  $values[] = "(".implode(",",$vals).")";
  
  if(!$i++)
    $fields = implode(",",array_keys((array)$mp));

}

$where = implode("||",$where);
$values = implode(",",$values);

try { 

  echo "Updating mp table\n";
  DB::get(1)->query("start transaction;");

  $r = DB::get(1)->query("delete from mp where {$where};");
  $count = $r->rowCount();
  echo "Deleted {$count} entr".($count==1?"y":"ies")." from mp_table\n";
  
  $r = DB::get(1)->query("insert into mp ({$fields}) values {$values}");
  $count = $r->rowCount();
  echo "Inserted {$count} entr".($count==1?"y":"ies")." into mp_table\n";
  
  DB::get(1)->query("commit;");
  echo "Committed\n";
  
} catch(PDOException $e){

  var_dump(DB::get(1)->errorInfo());
  DB::get(1)->query("rollback;");
  die("Failed at updating mp table\n");
  
}

