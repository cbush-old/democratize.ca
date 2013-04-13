#!/usr/bin/env php
<?php

require_once "../base/codes.php";
require_once "../base/db.php";

$res = DB::query("select * from riding");

$i = 0;

$values = array();

while($row = $res->fetchObject()){

  $row->lcname = lcname($row->name);
  
  $row = (array)$row;
  
  if(!$i++)
    $fields = implode(",", array_keys($row));
  
  foreach($row as &$v){
    $v = DB::get(1)->quote($v);
  }
  
  $values[] = "(".implode(",",$row).")";
  
}

$values = implode(",",$values);

DB::get(1)->query("start transaction;");

$query = "insert into riding ({$fields}) values {$values}";

try {

  DB::get(1)->query("delete from `riding`");
  DB::get(1)->query($query);
  DB::get(1)->query("commit;");

} catch(PDOException $e){

  var_dump($e);
  DB::get(1)->query("rollback;");
  
}

