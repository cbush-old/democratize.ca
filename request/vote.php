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
      throw new HTTP_status(400);
    
    $pscn = "{$ps}-{$cn}";
    
    
    
    
    $this->response = $_SERVER["REMOTE_ADDR"];
    
    
  }
  
}
