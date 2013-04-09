<?php
require_once("../config.php");

class DB {

  private function __construct(){
    $this->handle = new PDO (
      DB_DSN,
      DB_USER,
      DB_PASS
    );
  }

  public static function get(){
    static $h = NULL;
    if(!$h)
      $h = new DB();
    return $h->handle;
  }

  public static function query($query){
    return self::get()->query($query);
  }

}

