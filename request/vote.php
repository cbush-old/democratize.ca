<?php

class Vote_request extends Request {

  public function GET($args){
    
    $this->response->votes = array();
    
    $req = new StdClass;
    $req->parl = 0;
    $req->sess = 0;
    $req->chamber = "";
    $req->number = 0;
    
    foreach($args as $arg){
      if(preg_match("/^([1-9][0-9])(?:-([1-9]))?$/",$arg,$match)){
        if(isset($match[1])) $req->parl = $match[1];
        if(isset($match[2])) $req->sess = $match[2];
      } else if(preg_match("/^(c|s|t|u)(?:-?([1-9][0-9]{0,4}))$/",$arg,$match)){
        if(isset($match[1])) $req->chamber = strtoupper($match[1]);
        if(isset($match[2])) $req->number = $match[2];
      }
    }
    
    if(!$req->parl||!$req->sess||!$req->chamber||!$req->number){
      
      $r = DB::query("select
        sum(vote_yes) as vote_yes,
        sum(vote_no) as vote_no,
        count(*) as vote_total,
        pscn from vote group by pscn
      ");
      
      while($row = $r->fetchObject())
        $this->response->votes[] = $row;
      
    } else {
    
      $pscn = "{$req->parl}-{$req->sess}/{$req->chamber}-{$req->number}";
      $r = DB::query("select 
        sum(vote_yes) as vote_yes,
        sum(vote_no) as vote_no,
        count(*) as vote_total
        from vote where pscn = '{$pscn}'
        group by pscn");
  
      if(!$r->rowCount()){
        $row = new StdClass;
        $row->vote_yes = 0;
        $row->vote_no = 0;
        $row->vote_total = 0;
        $this->response->votes[] = $row;
        return;
      }
      
      while($row = $r->fetchObject())
        $this->response->votes[] = $row;
    
      
    }
  
  }
  
  public function POST($arg){
  
    $ps = array_shift($arg);
    $cn = array_shift($arg);
    
    if(!preg_match("/^[1-9][0-9]-[1-9]$/",$ps)
    ||!preg_match("/^(c|s|t|u)-[1-9][0-9]{0,4}$/",$cn))
      return "No bill specified";
      
    $pscn = "{$ps}/{$cn}";    
    
    $count = DB::query("select pscn from bill where pscn='{$pscn}' limit 1")->rowCount();
    
    if(!$count)
      return "Bill {$pscn} not found";
    
    if(!isset($_POST['vote']))
      return "No vote sent";
  
    $vote = trim(strtolower($_POST['vote']));
    
    if($vote!="y"&&$vote!="n")
      return "Vote must be 'y' or 'n'";
    
    if(!isset($_POST['fsa']))
      return "Valid FSA required";
      
    $fsa = strtoupper($_POST['fsa']);
    
    if(!preg_match("/^[A-Z][0-9][A-Z]{1,2}$/", $fsa))
      return "Invalid FSA format";
    

    // Check that FSA exists, once the FSA table is complete....
    // $count = DB::query("select fsa from fsa where fsa='{$fsa}' limit 1")->rowCount();
  
    $entry['pscn'] = $pscn;
    $entry['date'] = date("Y-m-d H:i:s", time());
    $entry['ip_hash'] = sha1($_SERVER["REMOTE_ADDR"]);
    $entry['user'] = 0; // not implemented  
    $entry['fsa'] = $fsa;
    
    if(isset($_POST['profile'])){
      $profile = json_decode($_POST['profile']);
      if($profile) 
        $entry['profile'] = json_encode($profile);
    }

    $vote=='y'
      and $entry['vote_yes'] = 1
      or $entry['vote_no'] = 1;
    
    foreach($entry as &$v)
      $v = DB::get(1)->quote($v);
  
    $fields = implode(",", array_keys($entry));
    $values = implode(",", $entry);
    
    try { 

      DB::get(1)->query("start transaction;");
    
      $r = DB::get(1)->query(
        "delete from `vote` 
        where ip_hash = {$entry['ip_hash']}
        && pscn = {$entry['pscn']}"
      );
      $this->response->deleted = $r->rowCount();

      $r = DB::get(1)->query("insert into `vote` ({$fields}) values ({$values})");
      $this->response->inserted = $r->rowCount();
      DB::get(1)->query("commit;");
    
    } catch(PDOException $e){
    
      DB::get(1)->query("rollback;");
      return "{$e->errorInfo[2]}";
      
    }
  }
}
