<?php

class Publication_request extends Request {

  public function GET($args){
  
    $select = array(
      "publication.pscn as bill",
      "publication.parl_id",
      "publication.title_en",
      "publication.title_fr"
    );
    
    $select = implode(",",$select);
    
    $where = array();
    
    foreach($args as $arg){
      if(preg_match("/^([1-9][0-9])(?:-?([1-9]))?$/", $arg, $match)){
        if(isset($match[1])) $where[] = "bill.parl='{$match[1]}'";
        if(isset($match[2])) $where[] = "bill.sess='{$match[2]}'";
      } else if(preg_match("/^(c|s|t|u)-?([1-9][0-9]{0,4})?$/", $arg, $match)){
        if(isset($match[1])) $where[] = "bill.chamber='".strtoupper($match[1])."'";
        if(isset($match[2])) $where[] = "bill.number='{$match[2]}'";
      }
    }
    
    $where = implode("&&", $where);
    if($where) $where = "where {$where}";
    
    $r = DB::query("select {$select} from bill
      join publication on publication.pscn = bill.pscn
      {$where} group by parl_id");
    
    $this->response->publications = array();
    
    while($row = $r->fetchObject()){
      $this->response->publications[] = $row;
    
    }
  
  }
  
}
