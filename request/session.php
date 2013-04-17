<?php

require(__DIR__."/../base/bcrypt.php");

class Session_request extends Request {
  
  public function POST($args){
  
    // Authenticate the user, then set a session cookie.
    
    $bcrypt = new bcrypt();
    
    $required = array("user","pass");
    $missing = array();
    
    foreach($required as $k){
      if(!isset($_POST[$k]))
        $missing[] = $k;
    }
    
    if(count($missing))
      return array("missing"=>$missing);
      
    $value = DB::get()->quote($_POST['user']);
    $key = strpos($value,'@') ? 'email':'username';
    
    $hash = DB::get()->quote($bcrypt->hash($_POST['pass']));

    $r = DB::query("select email, hash from user where {$key}={$value}");
    
    if($r->rowCount()==0)
      return ucfirst($key)." not found";
    
    $row = $r->fetchObject();
    
    if(!$bcrypt->verify($_POST['pass'],$row->hash))
      return "Password incorrect".$hash.$row->hash;
      
    $user = DB::get()->quote($row->email);
    $created = DB::get()->quote(time());
    $session_id = DB::get()->quote(sha1($user.$created));
    
    
    $values = implode(",",array($session_id,$user,$created));
    
    
    try {
    
      DB::get(1)->query("start transaction;");
      DB::get(1)->query("delete from session where user={$user};");
      DB::get(1)->query("insert into session (id, user, created) values ({$values})");
      DB::get(1)->query("commit;");
      setcookie("session", $session_id);
      $this->response->logged_in = true;

    } catch(PDOException $e){
    
      DB::get(1)->query("rollback;");
      return implode("/", (array)$e);
      
    }

  }
  
  public function DELETE($args){
  
    if(!isset($_COOKIE["id"]))
      return "Not logged in";
      
    $id = DB::get(1)->quote($_COOKIE["id"]);
    
    try {
    
      $r = DB::get(1)->query("delete from session where id={$id}");
      if(!$r->rowCount())
        return "No sessions deleted";
      $this->response->logged_out = true;
      
    } catch(PDOException $e){
      
      return implode("/",(array)$e);
      
    }

  }

}
