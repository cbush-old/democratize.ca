<?php


// Save and return the HTTP request body:

function request_body(){
  
  static $data = null;
  if(!$data)
    $data = file_get_contents('php://input');
  return $data;
  
}



class Request {

  public $method;
  public $response;
  
  public function __construct($method, $args){
    
    if(!method_exists($this,$method))
      throw new HTTP_status (405, array("Allow:",$this->get_allow_str()));
    
    $this->response = new StdClass;
    $this->$method($args);
  
  }
  
  // //  Implement these in your subclass to enable each method
  // public function GET($args)
  // public function POST($args)
  // public function PUT($args)
  // public function DELETE($args)
  
  public function get_response(){
  
    return $this->response;

  }
  
  private function get_allow_str(){
    
    static $methods = array("GET","POST","PUT","DELETE");
    
    $allow = array();
    
    foreach($methods as $m)
      if(method_exists($this,$m)) 
        $allow[] = $m;
      
    return implode(", ", $allow);
  
  }
  
}
