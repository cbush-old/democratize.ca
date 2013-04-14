<?php

class Bill_request extends Request {
  
  public function GET($args){
  
    $req = new StdClass();
    $req->chamber = "C"; // some defaults
    $req->number = ""; 
    $req->parl = "41";
    $req->sess = "1";
    $req->party = "";
    $req->subject = "";
    $req->mp = "";
    $req->order = array();
    $req->expect = 0;
    
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
        static $parties = array(
          "cpc"=>1,"lpc"=>1,"ndp"=>1,"bq"=>1,"gp"=>1,"pc"=>1,"ind"=>1
        );
        if(!isset($parties[$arg])) 
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
      },
      
      "subject" => function($arg, &$req){
        if($req->expect!=2||!preg_match("/^[a-z-]{4,}$/",$arg)) 
          return false;
        $req->expect = 0;
        return $req->subject = $arg;
      },
      
      "mp" => function($arg, &$req){
        if($req->expect!=1||!preg_match("/^[a-z-]{6,}$/",$arg)) 
          return false;
        $req->expect = 0;
        return $req->mp = $arg;
      }
    );
    
    $pre = array("mp"=>1,"subject"=>2);
    
    foreach($args as $arg){
      if(isset($pre[$arg])){
        $req->expect = $pre[$arg];
        continue;
      }
      foreach($arg_func as $k => $f){
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
      "(select count(*) from comment where comment.pscn = bill.pscn) as n_comments"
    );
    
    static $tag_jsonifier = "concat('[',group_concat(distinct concat(
      '{\"en\":\"',subject.name_en,'\",\"fr\":\"',subject.name_fr,'\"}'
      ) separator ','),']')";

    if($req->subject){
    
      $select[] = "(select
        {$tag_jsonifier}
        from bill_subject
        join subject on subject.id = bill_subject.subject_id
        where bill_subject.pscn = bill.pscn
      ) as tags";
    
    } else {
    
      $select[] = "{$tag_jsonifier} as tags";
   
    }
    
    
    // FROM
    ///////
    
    $table = "bill";

    // JOIN
    ///////
    
    $joins = array(
      "left join alias_mp on alias_mp.alias = bill.mp_alias",
      "left join mp on mp.lcname = alias_mp.mp_lcname",
      "left join riding on riding.lcname = mp.riding_lcname",
      // "left join summary on summary.pscn = bill.pscn",
      "left join bill_subject on bill_subject.pscn = bill.pscn",
      "left join subject on subject.id = bill_subject.subject_id",
      "left join alias_subject on alias_subject.subject_id = subject.id"
    );
    
    // WHERE
    ////////
    
    $cond = array();
    $req->parl and $cond[] = "`parl`='{$req->parl}'";
    $req->sess and $cond[] = "`sess`='{$req->sess}'";
    $req->chamber and $cond[] = "`chamber`='{$req->chamber}'";
    $req->number and $cond[] = "`number`='{$req->number}'";
    $req->party and $cond[] = "`party`='{$req->party}'";
    $req->subject and $cond[] = "`alias_subject`.`alias`='{$req->subject}'";
    $req->mp and $cond[] = "`bill`.`mp_alias`='{$req->mp}'";

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
    
    $limit = "";
    
    
  

    $select = implode(",", $select);
    $joins = implode(" ", $joins);
    $cond = implode("&&",$cond);
    $order = implode(",",$order);
    

    $query = "select {$select} from {$table} {$joins} where {$cond} "
      . "{$group_by} order by {$order} {$limit}";
    
    
    
    $result = DB::query($query);
  
    

    $this->response->n_results = $result->rowCount();
    $this->response->bills = array();
    
    while($r = $result->fetchObject()){
      
      if($r->tags){
        $r->tags = json_decode($r->tags);
      
      }
      $this->response->bills[] = $r;
      
    }
    
  }

}
