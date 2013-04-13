<?php

class Summary_request extends Request {

  public function GET($args){

    $result = DB::query("select * from summary");
    
    while($r = $result->fetchObject())
      $this->response->summaries[] = $r;
      
  }

}
