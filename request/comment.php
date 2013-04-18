<?php

class Comment_request extends Request {

  public function GET($args){
    
    $select = array(
      "comment.lft",
      "comment.rgt",
      "user.name",
      "user.username",
      "user.id",
      "comment.created",
      "comment.modified",
      "comment.gist",
      "comment.detail"
    );
    
    $where = array();
    $order = array();
    
    $cmd = array_shift($args);
  
    if($cmd=="id"){
    
      $id = array_shift($args);
      if(!$id)
        return "Missing id";
      
      $id = DB::get()->quote($id);
      
      $where[] = "comment.id={$id}";
    
    } else if(!$cmd||$cmd=="latest"){
      
      $select[] = "comment.pscn";
      $order[] = "created desc";
      
    } else if(preg_match("/^([1-9][0-9]-[1-9])$/",$cmd)){
      
      $ps = $cmd;
      
      $cn = array_shift($args);
      
      if(!preg_match("/^(c|s|t|u)-[1-9][0-9]{0,4}$/",$cn))
        return "Invalid parl-session/bill-number";
      
      $pscn = DB::get()->quote("{$ps}/{$cn}");
      
      $where[] = "comment.pscn={$pscn}";
      $order[] = "lft asc";
      
    }
    
    $select = implode(",",$select);
    $where = implode("&&",$where);
    $order = implode(",",$order);
    if($where) $where = "where {$where}";
    if($order) $order = "order by {$order}";
    
    $r = DB::query("select {$select} 
      from comment 
      join user on user.id = comment.user
      {$where} {$order}"
    );

    $this->response->comments = array();
    
    while($row = $r->fetchObject())
      $this->response->comments[] = $row;
    
  }
  
  public function POST($args){
    
    $user = authenticated();
  
    // TODO: make this function throw instead of checking this 
    // every time?
    if(!$user) return "Not logged in";
  
    // TODO: Check that user isn't spamming
    
    $required = array("bill","gist","detail","after");
    $missing = array();
    
    foreach($required as $k)
      if(!isset($_POST[$k]))
        $missing[] = $k;
    
    if(count($missing))
      return array("missing"=>$missing);
    
    try {
    
      $values['pscn'] = $_POST['bill'];
      $values['lft'] = intval($_POST['after']) + 1;
      
      if($values['lft'] < 0)
        return "'after' value must be > 0";
      
      // TODO:
      // Check for max rgt so pranksters don't mess up the tree?
        
      $values['rgt'] = $values['lft'] + 1;
      $values['user'] = $user;
      $values['created'] = date("Y-m-d H:i:s",time());
      $values['gist'] = $_POST['gist'];
      $values['detail'] = $_POST['detail'];
      
      foreach($values as &$v)
        $v = DB::get(1)->quote($v);
      
      DB::get(1)->query("start transaction;");
      DB::get(1)->query("update comment set rgt=rgt+2 "
        ."where pscn={$values['pscn']} && rgt >= {$values['lft']}");
      DB::get(1)->query("update comment set lft=lft+2 "
        ."where pscn={$values['pscn']} && lft >= {$values['lft']}");
        
      $fields = implode(",",array_keys($values));
      $values = implode(",",$values);
      
      DB::get(1)->query(
        "insert into comment ({$fields}) values ({$values})"
      );
      DB::get(1)->query("commit;");
      
      $this->response->success = true;
      
    } catch(PDOException $e){
    
      DB::get(1)->query("rollback;");
      return $e;
    
    }
  
  }
  
  public function PUT($args){
  
  
  }
  
  public function DELETE($args){
  
  
  }

}
