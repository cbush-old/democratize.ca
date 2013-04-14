<?php

class Subject_request extends Request {
  
  public function GET($args){
      
    // no args: return list of subjects
    
    $r = DB::query("select name_en, name_fr, 
      group_concat(alias separator ', ') as alias,
      (select count(*) from bill_subject
      where bill_subject.subject_id = subject.id
      ) as n
      from subject
      join alias_subject on alias_subject.subject_id = subject.id
      group by subject.id
      order by name_en
    ");
    
    $this->response->subjects = array();
    
    while($row = $r->fetchObject())
      $this->response->subjects[] = $row;


  }

}

