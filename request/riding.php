<?php

class Riding_request extends Request {

  public function GET($args){
  
    $select = array(
      "riding.name",
      "riding.province",
      "riding.population",
      "riding.voters",
      "riding.polling_divisions",
      "mp.lcname as mp"
    );
  
    $select = implode(",",$select);
    
    $where = array();
    
    static $provs = array(
      "qc"=>1,
      "bc"=>1,
      "ab"=>1,
      "sk"=>1,
      "mb"=>1,
      "on"=>1,
      "qc"=>1,
      "nl"=>1,
      "nb"=>1,
      "pei"=>1,
      "nwt"=>1,
      "nv"=>1,
      "yk"=>1,    
    );
    
    foreach($args as $arg){
      if(isset($provs[$arg]))
        $where[] = "province = '{$arg}'";
        
      else if(preg_match("/^[a-z-]+$/", $arg))
        $where[] = "riding.lcname like '%{$arg}%'";
      
    }
    
    $where = implode(",",$where);
    if($where) $where = "where {$where}";
    
    $r = DB::query("select {$select} from riding 
      join mp on mp.riding_lcname = riding.lcname
      {$where}
      group by riding.lcname
    ");
    
    $this->response->ridings = array();
    
    while($row = $r->fetchObject())
      $this->response->ridings[] = $row;
  
  }
  
}

