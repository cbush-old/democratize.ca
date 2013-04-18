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
    
    $r = DB::query("select id, hash from user where {$key}={$value}");
    
    if($r->rowCount()==0)
      return ucfirst($key)." not found";
    
    $row = $r->fetchObject();
    
    if(!$bcrypt->verify($_POST['pass'],$row->hash))
      return "Password incorrect".$hash.$row->hash;
      
    $user = DB::get()->quote($row->id);
    $created = DB::get()->quote(time());
    $id = sha1(sha1($user.$created).sha1(mt_rand())).sha1(mt_rand());
    $id_quoted = DB::get()->quote($id);
    $ip = DB::get()->quote(sha1($_SERVER["REMOTE_ADDR"]));
    
    $values = implode(",",array($id_quoted,$ip,$user,$created));
    
    try {
    
      DB::get(1)->query("start transaction;");
      DB::get(1)->query("delete from session where user={$user};");
      DB::get(1)->query("insert into session (id, ip, user, created) values ({$values})");
      DB::get(1)->query("commit;");
      setcookie("session", $id);
      $this->response->logged_in = true;

    } catch(PDOException $e){
    
      DB::get(1)->query("rollback;");
      return implode("/", (array)$e);
      
    }

  }
  
  public function DELETE($args){
  
    $user = authenticated();
    
    if(!$user)
      return "Not logged in";
      
    $user = DB::get(1)->quote($user);
    
    try {
      
      $r = DB::get(1)->query("delete from session where user={$user}");
      if(!$r->rowCount())
        return "No sessions deleted";
      $this->response->logged_out = true;
      
    } catch(PDOException $e){
      
      return implode("/",(array)$e);
      
    }

  }

}
