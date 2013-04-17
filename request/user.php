<?php

require_once(__DIR__."/../base/bcrypt.php");


class User_request extends Request {

  public function GET($args){
    
  }
  
  public function POST($args){
    
    $bcrypt = new bcrypt();
    
    $missing = array();
    $required = array("email","pass");
    
    foreach($required as $k){
     
      if(!isset($_POST[$k]))
        $missing[] = $k;
        
    }
    
    if(count($missing))
      return array("missing"=>$missing);
    
    if(!preg_match("/^[A-z0-9-_\.]+@[A-z0-9-_\.]+\.[a-z]{2,4}$/",$_POST["email"]))
      return "Invalid e-mail address format";
    
    if(strlen($_POST["pass"]) < 6)
      return "Password must be at least 6 characters long";
    
    $email = DB::get()->quote($_POST['email']);
    
    $r = DB::query("select email from user where email={$email}");
    
    if($r->rowCount())
      return "E-mail already in use for another account";
    
    if(isset($_POST['riding'])){
      $riding = DB::get()->quote($_POST['riding']);
      $r = DB::query("select count(*) from riding where lcname={$riding}");
      if(!$r->rowCount()){
        $this->response->notice[] = "Riding {$riding} not found; ignored";
        $riding = "";
      }
    } else {
      $riding = "";
    }
    
    
    $values = array();
    $values['email'] = $_POST['email'];
    $values['hash'] = $bcrypt->hash($_POST['pass']);
    $values['name'] = isset($_POST['name']) ? $_POST['name']:"Virtual MP";
    $values['riding_lcname'] = $riding;
    
    if(isset($_POST['username']))
      $values['username'] = $_POST['username'];
    
    foreach($values as &$v)
      $v = DB::get(1)->quote($v);
      

    $fields = implode(",",array_keys($values));
    $values = implode(",",$values);
    
    try {
    
      $r = DB::get(1)->query("insert into user ({$fields}) values ({$values})");
      if($r->rowCount()){
        $this->response->success = true;
      } else {
        return "Unable to add user to database";
      }
    } catch(PDOException $e){
      
      return json_encode($e);
      
    }
    
    return false;
    
  }
  
  public function PUT($args){
    
  
  }
  
  public function DELETE($args){

  
  }
  
  
  
}
