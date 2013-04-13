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
  
    $req = new StdClass();
    $req->chamber = "C"; // some defaults
    $req->number = ""; 
    $req->parl = "41";
    $req->sess = "1";
    
    $req->party = "";
    $req->subject = "";
    
    $req->order = array();
    
    // define an array of callbacks for checking arguments.
    // key is just a description.
    $arg_func = array(
      
      "parl-sess" => function($arg, &$req){
        if(!preg_match("/^([0-9]{2})(?:-([1-9]))?$/", $arg, $match)) 
          return false;
        if(isset($match[1])) $req->parl = $match[1];
        if(isset($match[2])) $req->sess = $match[2];
        return true;
      },
      
      "chamber-number" => function($arg, &$req){
        if(!preg_match("/^(c|s|t|u)(?:-([1-9][0-9]{0,4}))?$/", $arg, $match)) 
          return false;
        if(isset($match[1])) $req->chamber = strtoupper($match[1]);
        if(isset($match[2])) $req->number = $match[2];
        return true;
      },
      
      "party" => function($arg, &$req){
        if(!isset(self::$parties[$arg])) 
          return false;
        return $req->party = $arg;
      },
      
      "sort" => function($arg, &$req){
        if($arg=="active") return $req->order[] = "updated desc";
        if($arg=="newest") return $req->order[] = "introduced desc";
        if($arg=="oldest") return $req->order[] = "introduced asc";
        if($arg=="popular") return $req->order[] = "votes_yes desc";
        if($arg=="unpopular") return $req->order[] = "votes_no desc";
        return false;
      }
    
    );
    
    foreach($args as $arg){
      foreach($arg_func as $k => $f){
        echo $k;
        if($f($arg, $req)){
          unset($arg_func[$k]);
          continue(2);
        }
      }
      $this->response->bad_args[] = $arg;
    }
    
    
    //  Query parts
    
    // SELECT 
    /////////
    
    $select = array(
      "bill.pscn",
      "bill.parl",
      "bill.sess",
      "bill.chamber",
      "bill.number",
      "bill.introduced",
      "bill.updated",
      "bill.type",
      "bill.status",
      "bill.short_title_en",
      "bill.short_title_fr",
      "bill.title_en",
      "bill.title_fr",
      "bill.mp_alias",
      "concat(mp.first_name,' ',mp.last_name) as sponsor",
      "riding.name as riding",
      "riding.province as province",
      "mp.party as party",
      "(select sum(vote_yes) from vote where vote.pscn = bill.pscn) as votes_yes",
      "(select sum(vote_no) from vote where vote.pscn = bill.pscn) as votes_no",
      "summary.summary_en",
      "summary.summary_fr",
      "group_concat(distinct subject.name_en separator ', ') as tags_en",
      "group_concat(distinct subject.name_fr separator ', ') as tags_fr",
      "(select count(*) from comment where comment.pscn = bill.pscn) as n_comments"
    );
    
    // FROM
    ///////
    
    $table = "bill";

    // JOIN
    ///////
    
    $joins = array(
      "left join alias_mp on alias_mp.alias = bill.mp_alias",
      "left join mp on mp.lcname = alias_mp.mp_lcname",
      "left join mp_riding on mp_riding.mp_lcname = mp.lcname",
      "left join riding on riding.lcname = mp_riding.riding_lcname",
      "left join summary on summary.pscn = bill.pscn",
      "left join bill_subject on bill_subject.pscn = bill.pscn",
      "left join subject on subject.id = bill_subject.subject_id"
    );
    
    // WHERE
    ////////
    
    $cond = array();
    $req->parl and $cond[] = "`parl`='{$req->parl}'";
    $req->sess and $cond[] = "`sess`='{$req->sess}'";
    $req->chamber and $cond[] = "`chamber`='{$req->chamber}'";
    $req->number and $cond[] = "`number`='{$req->number}'";
    $req->party and $cond[] = "`party`='{$req->party}'";
    $req->subject and $cond[] = 
      "bill.pscn IN (select pscn from bill_subject
        join subject on subject.id = bill_subject.subject_id
        join alias_subject on alias_subject.subject_id = subject.id
        where alias = '{$req->subject}'
        group by subject.id
      )";

    // GROUP BY
    ///////////
    
    $group_by = "group by bill.pscn";

    // ORDER BY
    ///////////
    
    $order = array(
      "bill.parl",
      "bill.sess",
      "bill.chamber",
      "bill.number"
    );
    
    // LIMIT
    ////////
    
    $limit = "limit 100";
    
    
  

    $select = implode(",", $select);
    $joins = implode(" ", $joins);
    $cond = implode("&&",$cond);
    $order = implode(",",$order);
    

    $query = "select {$select} from {$table} {$joins} where {$cond} "
      ."{$group_by} order by {$order} {$limit}";
    
    
    
    $result = DB::query($query);
  
    

    $this->response->n_results = $result->rowCount();
    $this->response->bills = array();
    
    while($r = $result->fetchObject()){

      $this->response->bills[] = $r;
      
    }
    
  }

}
