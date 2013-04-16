<?php

class Mp_request extends Request {

  public function GET($args){
    
    $select = array(
      "mp.first_name",
      "mp.last_name",
      "mp.lang_pref",
      "mp.party",
      "party.name_en as party_en",
      "party.name_fr as party_fr",
      "riding.name as riding",
      "riding.province as province",
      "(select count(*) from bill 
        where bill.mp_alias = alias_mp.alias 
        && alias_mp.mp_lcname = mp.lcname
      ) as n_bills"
    );

    $where = array();
    
    $parties = party_abbrev_array();
    
    foreach($args as $arg){
      
      if(isset($parties[$arg]))
        $where[] = "party='{$arg}'";
      
      else if(preg_match("/^[a-z-]{4,}$/",$arg))
        $where[] = "alias like '%{$arg}%'";
      
    }
    
    $select = implode(",", $select);
    $where = implode("&&", $where);
    if($where) $where = "where {$where}";
    
    $query = "select {$select} from mp 
      join party on party.id = mp.party
      join riding on riding.lcname = mp.riding_lcname
      join alias_mp on alias_mp.mp_lcname = mp.lcname
      join bill on bill.mp_alias = alias_mp.alias
      {$where}
      group by mp.lcname
      ";
    $result = DB::query($query);
      
    $this->response->n_results = $result->rowCount();
    
    $this->response->mps = array();
      
    
    while($r = $result->fetchObject()){
      
      $this->response->mps[] = $r;
    
    }

    return;

  }
  
}
