<?php

require_once(__DIR__."/../base/bcrypt.php");


class User_request extends Request {

  public function GET($args){
  
    $user = authenticated();
    
    if(!$user)
      return "
        <form method='post' action='../session'>
          <input type='text' name='user' />
          <input type='password' name='pass' />
          <input type='submit' value='&gt;' />
        </form>"; //test
    
    $this->response->user = $user;
    
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
    
    if(!is_valid_email($_POST["email"]))
      return "Invalid e-mail address format";
    
    if(strlen($_POST["pass"]) < 6)
      return "Password must be at least 6 characters long";
    
    $email = DB::get()->quote($_POST['email']);
    
    $r = DB::query("select email from user where email={$email}");
    
    if($r->rowCount())
      return "E-mail already in use for another account";

    $values = array();
    $values['email'] = $_POST['email'];
    $values['hash'] = $bcrypt->hash($_POST['pass']);
    $values['name'] = isset($_POST['name']) ? $_POST['name']:"Virtual MP";
    
    if(isset($_POST['riding']))
      $values['riding'] = $_POST['riding'];
      // note: this will fail and throw an error if riding doesn't exist.
  
    if(isset($_POST['username']))
      $values['username'] = $_POST['username'];
      // note: this will fail and throw an error if username is taken.
    
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
    
    $user = authenticate();
    
    if(!$user)
      return "Not logged in";
      
    $s = request_body_assoc();
    
    $values = array();
    
    if(isset($s['email'])){
      if(!is_valid_email($s['email']))
        return "Invalid e-mail address format";
      $values[] = "email=".DB::get(1)->quote($s['email']);
    }
    if(isset($s['name'])) $values[] = "name=".DB::get(1)->quote($s['name']);
    if(isset($s['riding'])) $values[] = "riding_lcname=".DB::get(1)->quote($s['riding']);
    if(isset($s['username'])) $values[] = "username=".DB::get(1)->quote($s['username']);
    
    implode(",",$values);
    
    $user = DB::get(1)->quote($user);
    
    try {
    
      DB::get(1)->query("start transaction;");
      DB::get(1)->query("update user set {$values} where id={$user}");
      DB::get(1)->query("commit;");
      
    } catch(PDOException $e){
    
      DB::get(1)->query("rollback;");
      return $e;
    
    }
    
    
  }

}
