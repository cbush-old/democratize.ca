<?php

class Mps_request extends Request {

  public function GET($args){
  
    $name = array_shift($args);
    
    $argstr = implode(" ",$args);
    
    if(!preg_match("/^[a-z-]+$/",$name))
      throw new HTTP_status (400);
      
    $bill = get_bill_from_uri_string($argstr);
    
    $name = (int)dmchash($name);
    
    $query =
      "select *
      from bills
      join bills_mps on bills.id = bills_mps.bill_id
      join mps on mps.parl_id = bills_mps.sponsor_parl_id
      left join ridings_mps on mps.id = ridings_mps.mp_id
      left join ridings on ridings_mps.riding_id = ridings.id
      where mps.id = {$name}
      group by bills.id";
      
    
    $result = DB::query($query);
  
    var_dump(DB::get()->errorInfo());
    
    while($r = $result->fetchObject())
      $this->response->bills[] = $r;
  
  
  }

}
