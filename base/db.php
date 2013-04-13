<?php
require_once(__DIR__."/../config.php");

class DB {

  private function __construct(){}

  public static function get($w = false){
  
    static $reader = null;
    static $writer = null;
    
    if(!$w){
      if(!$reader)
        $reader = new PDO (DB_PUB_DSN, 
          DB_READ_USER, DB_READ_PASS, 
          array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
        );
      return $reader;
    } else {
      if(!$writer)
        $writer = new PDO (DB_PUB_DSN, DB_WRITE_USER, DB_WRITE_PASS,
          array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
        );
      return $writer;
    }
    
  }

  public static function query($query){
    return self::get()->query($query);
  }

}

