<?php

class Event_request extends Request {

  public function GET($args){
  
    if(!count($args)) return "Specific bill pscn required";
    
    $select = array(
      "event.pscn as bill",
      "event.parl_id",
      "event.date",
      "event.meeting_number",
      "event.chamber",
      "event.status",
      "event.note",
      "event.committee"
    );
    
    $select = implode(",",$select);
    
    $where = array();
    
    foreach($args as $arg){
      if(preg_match("/^([1-9][0-9])(?:-?([1-9]))?$/", $arg, $match)){
        if(isset($match[1])) $where[] = "bill.parl='{$match[1]}'";
        else return "Specific bill pscn required";
        if(isset($match[2])) $where[] = "bill.sess='{$match[2]}'";
        else return "Specific bill pscn required";
      } else if(preg_match("/^(c|s|t|u)-?([1-9][0-9]{0,4})?$/", $arg, $match)){
        if(isset($match[1])) $where[] = "bill.chamber='".strtoupper($match[1])."'";
        else return "Specific bill pscn required";
        if(isset($match[2])) $where[] = "bill.number='{$match[2]}'";
        else return "Specific bill pscn required";
      }
    }
    
    
    $where = implode("&&", $where);
    if($where) $where = "where {$where}";
    
    $r = DB::query("select {$select} from bill
      join event on event.pscn = bill.pscn
      {$where} group by event.parl_id");
    
    $this->response->events = array();
    
    while($row = $r->fetchObject()){
      $this->response->events[] = $row;
    
    }
  
  }
  
}
