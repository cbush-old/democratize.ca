<?php
require_once(__DIR__."/../config.php");

class DB {

  private function __construct(){}

  public static function get($w = false){
  
    static $reader = null, $writer = null, $opts = array(
      PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    );
    
    if(!$w){
    
      if(!$reader)
        $reader = new PDO (DB_PUB_DSN, DB_READ_USER, DB_READ_PASS, $opts);
      return $reader;
      
    } else {
    
      if(!$writer)
        $writer = new PDO (DB_PUB_DSN, DB_WRITE_USER, DB_WRITE_PASS, $opts);
      return $writer;
      
    }
    
  }

  public static function query($query){
    return self::get()->query($query);
  }


}

