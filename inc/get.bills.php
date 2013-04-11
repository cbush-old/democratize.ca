<?php

//  This script is included from the main controller
//  and is tasked with assessing the get request,
//  assembling a query and filling the Response
//  object with the result.

$request = new StdClass();
$request->chamber = "C"; // some defaults
$request->number = ""; 
$request->parliament = "41";
$request->session = "1";
$request->parl_id = "";

$request->subject = "";

$request->p = 0;  // page (offset of the result set)
$request->n = 3;  // number of results
$request->sort = "updated";
$request->mode = "desc";

$request->get_sponsor = 0;
$request->get_summary = 0;
$request->get_tags = 0;
$request->get_votes_total = 0;
$request->get_votes_yes = 0;
$request->get_votes_no = 0;

$request->load_carousel = 1;

if(isset($_GET["sponsor"])) $request->get_sponsor = 1;
if(isset($_GET["summary"])) $request->get_summary = 1;
if(isset($_GET["tags"])) $request->get_tags = 1;
if(isset($_GET["votes"])) $request->get_votes = 1;
if(isset($_GET["no-carousel"])) $request->load_carousel = 0;




//  Query parts

$group_by = "group by bills.id";
$table = "bills";
$cond = array(); // where clause
$joins = array();




// main request specifiers - in URI

$getreq = array(
  $_GET["ctld"],
  $_GET["ctlc"],
  $_GET["ctlb"],
  $_GET["ctla"]
);

// found...
$f_bill = 0; 
$f_parl = 0;
$f_parl_id = 0;


$rx = bill_uri_regexes();


//  First check the base URI...

$base = array_pop($getreq);

if(preg_match("/^{$rx["party"]}$/", $base)){
  
  $request->get_sponsor = true;
  $cond[] = "party='{$base}'";

} else if(preg_match("/^{$rx["ok_base"]}$/", $base)){
  
  if($base=="latest"){
    
    $request->sort = "updated";
    $request->mode = "desc";
  
  } else if($base=="popular"){
  
    $request->get_votes_yes = true;
    $request->sort = "votes_yes";
    $request->mode = "desc";
    
  } else if($base=="unpopular"){
  
    $request->get_votes_no = true;
    $request->sort = "votes_no";
    $request->mode = "desc";
    
  } else if($base=="active"){
  
    $request->get_votes_total = true;
    $request->sort = "votes_total";
    $request->mode = "desc";
  
  } else if($base=="featured"){
  
  } else if($base=="mp"){
    
    
    
    
    
  }
  
} else {

  $getreq[] = $base;
  
}



foreach($getreq as $i => $q){
  
  if($q == "") continue;

  $matches = array();
  
  if(!$f_bill && preg_match("/^{$rx["bill_number"]}$/",$q,$matches)){
  
    if(isset($matches[1])) $request->chamber = strtoupper($matches[1]);
    if(isset($matches[2])) $request->number = $matches[2];
    $f_bill = 1;
    
  } else if(!$f_parl && preg_match("/^{$rx["parl_sess"]}?$/",$q,$matches)){

    if(isset($matches[1])) $request->parliament = $matches[1];
    if(isset($matches[2])) $request->session = $matches[2];
    $f_parl = 1;
    
  } else {
  
    notify_bad_arg("URI segment ".($i+1), $q, 
      "Expected parliament session, bill number or parl.gc.ca id");
  
  }

}


//  Other request variables, such as sorting order
{

static $ok_if_in_array = array(
  "mode" => array("asc", "desc", "a", "d", "ascending", "descending"),
  "sort" => array("introduced", "updated", "type", "status", "number")
  //  bill status and bill type could go here
);

static $ok_if_positive_number_less_than = array(
  "n" => BILLS_MAX_ENTRIES_PER_REQUEST,
  "p" => 100000
);

foreach($ok_if_in_array as $k => &$ok_array){

  if(!isset($_GET[$k])) continue;

  $v = &$_GET[$k];
  in_array($v, $ok_array)
    and $request->$k = $v
    or notify_bad_arg($k, $v, implode(", ",$ok_array)); 

}

foreach($ok_if_positive_number_less_than as $k => &$max){
  
  if(!isset($_GET[$k])) continue;

  $v = &$_GET[$k];
  (is_numeric($v) && (0 <= $v && $v <= $max))
    and $request->$k = $v
    or notify_bad_arg($k, $v, "Must be a positive number < {$max}");

}

}


// Construct the 'where' clause

$cond[] = "`parl_session`='{$request->parliament}-{$request->session}'";
$cond[] = "`chamber`='{$request->chamber}'";






// Begin constructing the query.
// Everything below "should" read from, rather than write to, the request.

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



//  detail options

if($request->get_sponsor){

  $table = "bills_mps";
  
  $select = array_merge($select, array(
    "concat (mps.first_name,\" \",mps.last_name) as sponsor",
    "mps.party",
    "ridings.name as sponsor_riding",
  ));
  
  $joins[] = "join bills on bills.id = bills_mps.bill_id";
  $joins[] = "left join mps on mps.parl_id = bills_mps.sponsor_parl_id";
  $joins[] = "left join ridings_mps on mps.id = ridings_mps.mp_id";
  $joins[] = "left join ridings on ridings_mps.riding_id = ridings.id";

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
    
  $joins[] = "left join `bills_subjects` on bills_subjects.bill_id = bills.id";
  $joins[] = "left join `subjects` on bills_subjects.subject_id = subjects.id";

}


$select2 = array();

if($request->get_votes_yes){

  $select2[] = "(
    select 
    sum(vote&1)
    from `user_votes`
    where bills.id = user_votes.bill_id
  ) as votes_yes
  ";
  
}

if($request->get_votes_no){

  $select2[] = "(
    select 
    round(sum(vote&2)/2)
    from `user_votes`
    where bills.id = user_votes.bill_id
  ) as votes_no
  ";
  
}

if($request->get_votes_total){

  $select2[] = "(
    select 
    count(*)
    from `user_votes`
    where bills.id = user_votes.bill_id
  ) as votes_total
  ";
  
}

$select = array_merge($select, $select2);

// Handle requests for subjects (separated by comma)

if(isset($_GET["subject"])){
  
  $subj = explode(",",$_GET["subject"]);
  $subcond = array();
  foreach($subj as $s){
    $s = dmchash($s);
    $subcond[] = "
      bills.id IN ( 
      select bill_id
      from bills_subjects
      where subject_id = '{$s}'
    )";
  }
  $cond[] = "(".implode("||",$subcond).")";
  
}


//  Coming together

$select = implode(", ", $select);
$joins = implode(" ", $joins);


$query = "";

if($request->number){

  if($request->load_carousel){


    //  Loading the carousel involves grabbing other bills than 
    //  the one specifically requested.

    //  This is meant to perform the same query, but only grab one
    //  column in order to determine the index of the row we want
    //  in the result set. The idea is to get previous and next 
    //  entries out of a set from an arbitrary non-numeric index...

    //  If anyone knows how to do this in a single query, let me know.

    $cond = implode("&&",$cond);
    
    $query = "from {$table} {$joins} where {$cond} {$group_by} 
      order by {$request->sort} {$request->mode}";
    
    $prequery = "from `bills` where {$cond} {$group_by} 
      order by {$request->sort} {$request->mode}";
    
    
    array_unshift($select2,"`bills`.number");
    
    $presult = DB::query("select ".implode(",",$select2)." {$prequery}");

    if(!$presult->rowCount()){
      
      $Response->n_results = 0;
      $query = "";
    
    } else {
    
      $p = 0;

      while($s = $presult->fetchColumn()){
        if($s==$request->number)
          break;
        ++$p;
      }
      
      $request->p = intval(max(ceil($p-$request->n/2), 0));
    
      $Response->target_index = $p - $request->p;
  
    }

  } else {

    //  Forget the carousel and just get the actual results

    $cond[] = "`bills`.number='{$request->number}'";
    $cond = implode("&&",$cond);

    $query = "from {$table} {$joins} where {$cond} {$group_by} 
      order by {$request->sort} {$request->mode}";

    $Response->target_index = 0;

  }
} else {

  $cond = implode("&&",$cond);

  $query = "from {$table} {$joins} where {$cond} {$group_by} 
    order by {$request->sort} {$request->mode}";

}


// ok to proceed with the query
if($query){

  $result = DB::query($q = "select {$select} {$query} 
    limit {$request->p},{$request->n}");

  $Response->n_results = $result->rowCount();

  $Response->bills = array();

  while($r = $result->fetchObject()){
    

    if(isset($r->sponsor)){
      $spons = lcname($r->sponsor);
      $r->sponsor_uri = "/mps/{$spons}/";
      $r->sponsor_img = "{$spons}-{$r->party}.jpg";
    }
    
    if(isset($r->parl_session) && isset($r->number))
      $r->object_uri = "/bills/{$r->parl_session}/{$r->number}/";
      
      
    $Response->bills[] = $r;
    
  }

}

