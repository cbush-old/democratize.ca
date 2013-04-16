<?php

class Summary_request extends Request {

  public function GET($args){
  
    $select = array(
      "summary.pscn as bill",
      "summary.summary_en",
      "summary.summary_fr",
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
      join summary on summary.pscn = bill.pscn
      {$where} group by bill.pscn");
    
    $this->response->summaries = array();
    
    while($row = $r->fetchObject()){
      $this->response->summaries[] = $row;
    
    }
  
  }
  
}
