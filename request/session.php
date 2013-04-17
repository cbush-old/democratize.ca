<?php

require(__DIR__."/../base/bcrypt.php");

class Session_request extends Request {
  
  public function POST($args){
  
    // Authenticate the user, then set a session cookie.
    
    if(count($args)!=1)
      return "Argument count must be exactly 1 (username)";
    
    $bcrypt = new bcrypt();
    $user = DB::get()->quote(array_pop($args));
    $hash = $bcrypt->hash($_POST["pass"]);

    $r = DB::query("select username, hash from user where username={$user}");
    
    if($r->rowCount()==0)
      return "Username not found";
    
    $row = $r->fetchObject();
    
    if(!$bcrypt->verify($hash, $row->hash))
      return "Password incorrect";
    
    $created = DB::get()->quote($created);
    $session_id = DB::get()->quote(sha1($user.$created));
    
    $values = implode(",",array($session_id,$user,$created));
    
    try {
    
      DB::get(1)->query("start transaction;");
      DB::get(1)->query("delete from session where username={$user};");
      DB::get(1)->query("insert into session (id, username, created) values ({$values})");
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
