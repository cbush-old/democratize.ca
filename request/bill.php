<?php

class Bill_request extends Request {

  static $uri_regex = array(
    "bill_number" => "/^(?:(c|s|u|t)-?)?([0-9]{1,5})$/",
    "parl_sess" => "/^(?:([0-9]{1,3})-?)([0-9]+)?$/",
    "name" => "/^([a-z-]+)$/"
  );
  
  static $modes = array(
    "latest"=>1,"popular"=>1,"unpopular"=>1,"active"=>1,"featured"=>1,
    "tags"=>1
  );
  
  static $parties = array(
    "cpc"=>1,"lpc"=>1,"ndp"=>1,"bq"=>1,"gp"=>1,"pc"=>1
  );
  
  
  public function GET($args){
  
    $request = new StdClass();
    $request->chamber = "C"; // some defaults
    $request->number = ""; 
    $request->parliament = "41";
    $request->session = "1";
    $request->parl_id = "";
    $request->party = "";
    $request->subject = "";

    $request->p = 0;  // page (offset of the result set)
    $request->n = -1;  // number of results
    $request->sort = "updated";
    $request->mode = "desc";

    $request->get_sponsor = 1;
    $request->get_summary = 0;
    $request->get_tags = 0;
    $request->get_votes_total = 0;
    $request->get_votes_yes = 0;
    $request->get_votes_no = 0;

    $request->load_carousel = 1;
  
    $rx = self::$uri_regex;
    
    foreach($args as $arg){
    
      $matches = array();
      
      if(isset(self::$modes[$arg])){
      
        switch($arg){
        
          case "latest":
            $request->sort = "updated";
            $request->mode = "desc";
            break;
            
          case "popular":  
            $request->get_votes_yes = true;
            $request->sort = "votes_yes";
            $request->mode = "desc";
            break;
            
          case "unpopular":
            $request->get_votes_no = true;
            $request->sort = "votes_no";
            $request->mode = "desc";
            break;
          
          case "active":
            $request->get_votes_total = true;
            $request->sort = "votes_total";
            $request->mode = "desc";
            break;

          case "featured":
            break;
            
          case "tags":
            $request->get_tags = 1;
            break;
            
        }
        
      } else if(isset(self::$parties[$arg])){
        
        $request->get_sponsor = 1;
        $request->party = $arg;
        
      } else if(preg_match($rx["name"], $arg)){
        
        $request->name = $arg;
        
      } else if(preg_match($rx["parl_sess"], $arg, $matches)){
      
        if(isset($matches[1])) $request->parliament = $matches[1];
        if(isset($matches[2])) $request->session = $matches[2];
        
      } else if(preg_match($rx["bill_number"], $arg, $matches)){
      
        if(isset($matches[1])) $request->chamber = strtoupper($matches[1]);
        if(isset($matches[2])) $request->number = $matches[2];
      
      }
    }
    
    //  Query parts
    
    // SELECT 
    /////////
    
    $select = array(
      "concat (bills.chamber,'-',bills.number) as number",
      "bills.parl_id",
      "bills.parl_session",
      "bills.introduced",
      "bills.updated",
      "bills.type",
      "bills.status"
    );
    
    foreach(active_lang_array() as $lang){
      $select[] = "bills.short_title_{$lang}";
      $select[] = "bills.title_{$lang}";
    }
    
    if($request->get_votes_yes){

      $select[] = "(
        select sum(vote&1)
        from `user_votes`
        where bills.id = user_votes.bill_id
      ) as votes_yes";
      
    }

    if($request->get_votes_no){

      $select[] = "(
        select round(sum(vote&2)/2)
        from `user_votes`
        where bills.id = user_votes.bill_id
      ) as votes_no";
      
    }

    if($request->get_votes_total){

      $select[] = "(
        select count(*)
        from `user_votes`
        where bills.id = user_votes.bill_id
      ) as votes_total";
      
    }

    // FROM
    ///////
    
    $table = "bills";
    
    
    // WHERE
    ////////
    
    $cond = array(
      "`parl_session`='{$request->parliament}-{$request->session}'",
      "`chamber`='{$request->chamber}'"
    ); 
    
    if($request->party) 
      $cond[] = "party='{$request->party}'";
    
    if(isset($_GET["subject"])){
      
      $subj = explode(",",$_GET["subject"]);
      $subcond = array();
      foreach($subj as $s){
        $s = dmchash($s);
        $subcond[] = "bills.id IN ( select bill_id from bills_subjects
          where subject_id = '{$s}' )";
      }
      $cond[] = "(".implode("||",$subcond).")";
      
    }
    
    if($request->number) 
      $cond[] = "`bills`.number='{$request->number}'";
    
    
    // JOIN
    ///////
    
    $joins = array();
    
    if($request->get_sponsor){

      $select = array_merge($select, array(
        "concat (mps.first_name,\" \",mps.last_name) as sponsor",
        "mps.party",
        "ridings.name as sponsor_riding",
      ));
      
      $joins[] = 
        "join bills_mps on bills.id = bills_mps.bill_id "
        ."left join mps on mps.parl_id = bills_mps.sponsor_parl_id "
        ."left join ridings_mps on mps.id = ridings_mps.mp_id "
        ."left join ridings on ridings_mps.riding_id = ridings.id";

    }
    
    if($request->get_summary){

      foreach(active_lang_array() as $lang)
        $select[] = "bill_summaries.summary_{$lang}";
      
      $joins[] = 
        "left join bill_summaries on bills.id = bill_summaries.bill_id";

    }

    if($request->get_tags){
     
      foreach(active_lang_array() as $lang){
        $select[] = "
          group_concat(
            distinct
            subjects.name_{$lang}
            separator ', '
          ) as tags_{$lang}
        ";
      }
        
      $joins[] = 
        "left join `bills_subjects` on bills_subjects.bill_id = bills.id "
        ."left join `subjects` on bills_subjects.subject_id = subjects.id";

    }
  
  
    // GROUP BY
    ///////////
    
    $group_by = "group by bills.id";
    
    
    
    // LIMIT
    ////////
    
    
    if(isset($_GET["n"])){
      $n = $_GET["n"];
      if(is_numeric($n) && $n > 0)
        $limit = "limit {$n}";
    } else {
      $limit = "";
    }
    
    
  
  
    $select = implode(",", $select);
    $joins = implode(" ", $joins);
    $cond = implode("&&",$cond);
  
  
  
    
    
    $result = DB::query($q = 
      "select {$select} from {$table} {$joins} ".
      "where {$cond} {$group_by} ".
      "order by {$request->sort} {$request->mode} ".
      "{$limit}"
    );
  
  
    
    
    $this->response = new StdClass;
    $this->response->n_results = $result->rowCount();
    $this->response->bills = array();
    
    while($r = $result->fetchObject()){

      if(isset($r->sponsor)){
        $spons = lcname($r->sponsor);
        $r->sponsor_uri = "/mps/{$spons}/";
        $r->sponsor_img = "{$spons}-{$r->party}.jpg";
      }
      
      if(isset($r->parl_session) && isset($r->number))
        $r->object_uri = "/bills/{$r->parl_session}/{$r->number}/";
        
        
      $this->response->bills[] = $r;
      
    }
    
  }

}
