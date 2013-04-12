<?php

class HTTP_status extends Exception {

  public $code, $reason;
  
  public function __construct($code, $params = array()){
    $this->code = $code;
    
    if(is_array($params)){
    
      $msg = array();
        foreach($params as $k=>$v)
          $msg[] = "$k: {$v}";
      
      $msg = implode("; ", $msg);
    
    } else $msg = $params;
    
    $this->reason = $msg || " ";
    
  }
  
}

