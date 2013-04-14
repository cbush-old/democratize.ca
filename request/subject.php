<?php

class Subject_request extends Request {
  
  public function GET($args){
  
    if(!count($args)){
      
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
    
      return;

    }
    
    $q = DB::get()->quote(array_shift($args));
    
    $r = DB::query("select
      bill.*,
      concat(mp.first_name,' ',mp.last_name) as sponsor,
      riding.name as riding,
      riding.province as province
      from bill_subject
      right join subject on subject.id = bill_subject.subject_id
      right join alias_subject on alias_subject.subject_id = subject.id
      left join bill on bill.pscn = bill_subject.pscn
      left join alias_mp on alias_mp.alias = bill.mp_alias
      left join mp on mp.lcname = alias_mp.mp_lcname
      left join riding on riding.lcname = mp.riding_lcname
      where alias_subject.alias={$q}
      group by bill.pscn
      order by updated desc
    ");
    
    $this->response->subjects = array();
    
    while($row = $r->fetchObject()){
      $this->response->subjects[] = $row;
    
    }
    
  }

}

