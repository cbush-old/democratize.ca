<?php

class Vote_request extends Request {

  public function GET($args){
  
    $select = array(
      "vote.pscn as bill",
      "sum(vote.yes) as yes",
      "sum(vote.no) as no",
      "count(*) as total"
    );
    
    $where = array();
    $by_riding = 0;
    
    foreach($args as $arg){
      if(preg_match("/^([1-9][0-9])(?:-?([1-9]))?$/", $arg, $match)){
        if(isset($match[1])) $where[] = "bill.parl='{$match[1]}'";
        if(isset($match[2])) $where[] = "bill.sess='{$match[2]}'";
      } else if(preg_match("/^(c|s|t|u)-?([1-9][0-9]{0,4})?$/", $arg, $match)){
        if(isset($match[1])) $where[] = "bill.chamber='".strtoupper($match[1])."'";
        if(isset($match[2])) $where[] = "bill.number='{$match[2]}'";
      } else if($arg=="by-riding"){
        $by_riding = 1;
      }
    }
    
    $where = implode("&&", $where);
    if($where) $where = "where {$where}";
    
    $join = array("join vote on vote.pscn = bill.pscn");
    
    $group = array("bill.pscn");
    
    if($by_riding){
      $select[] = "riding.lcname as riding_lcname";
      $select[] = "riding.name as riding";
      $join[] = "left join fsa on fsa.code = vote.fsa";
      $join[] = "left join riding on riding.lcname = fsa.riding_lcname";
      array_unshift($group, "riding.lcname");
    }
    
    $select = implode(",",$select);
    $join = implode(" ", $join);
    $group = implode(",", $group);
    
    $r = DB::query("select {$select} from bill
      {$join} {$where} group by {$group}");
    
    $this->response->votes = array();
    
    while($row = $r->fetchObject()){
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
      and $entry['yes'] = 1
      or $entry['no'] = 1;
    
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
