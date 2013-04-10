<?php

class HTTP_status extends Exception {

  public $code, $reason;
  
  public function __construct($code, $params = array()){
    $this->code = $code;
    
    $msg = array();
    foreach($params as $k=>$v)
      $msg[] = "$k: {$v}";
    
    $this->reason = implode("; ", $msg) || " ";
    
  }
  
}


class MISSING_CONTROLLER extends Exception {

  public $which;
  
  public function __construct($path){
    $this->which = $path;
  }

}
