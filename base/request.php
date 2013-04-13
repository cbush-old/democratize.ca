<?php

class Request {

  public $method;
  public $response;
  
  public function __construct($method, $args){
    
    if(!method_exists($this,$method))
      throw new HTTP_status (405, array("Allow:",$this->get_allow_str()));
    
    $this->response = new StdClass;
    $this->method = $method;
    $this->$method($args);
  
  }
  
  // public function GET($args)
  // public function POST($args)
  // public function PUT($args)
  // public function DELETE($args)
  
  public function get_response(){
  
    return $this->response;

  }
  
  private function get_allow_str(){
    $allow = array();
    method_exists($this,"GET") and $allow[] = "GET";
    method_exists($this,"POST") and $allow[] = "POST";
    method_exists($this,"PUT") and $allow[] = "PUT";
    method_exists($this,"DELETE") and $allow[] = "DELETE";
    return implode(", ", $allow);
  }
  
}
