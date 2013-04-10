<?php

//  This script is included from the controller
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

$request->p = 0;
$request->n = 3;
$request->sort = "updated";
$request->mode = "desc";

$request->get_sponsor = 0;
$request->get_summary = 0;
$request->get_tags = 0;
$request->get_votes = 0;

if(isset($_GET["sponsor"])) $request->get_sponsor = 1;
if(isset($_GET["summary"])) $request->get_summary = 1;
if(isset($_GET["tags"])) $request->get_tags = 1;
if(isset($_GET["votes"])) $request->get_votes = 1;




// main request specifiers - in URI

$getreq = array(
  $_GET["ctlb"],
  $_GET["ctlc"]
);

// found...
$f_bill = 0; 
$f_parl = 0;
$f_parl_id = 0;



foreach($getreq as $i => $q){
  
  if($q == "") continue;

  $matches = array();
  
  if(!$f_bill && preg_match("/^(c|s|u|t)?(?:-?([0-9]{1,5}))?$/",$q,$matches)){
  
    if(isset($matches[1])) $request->chamber = strtoupper($matches[1]);
    if(isset($matches[2])) $request->number = $matches[2];
    $f_bill = 1;
    
  } else if(!$f_parl && preg_match("/^([0-9]{1,3})(?:-([0-9]+))?$/",$q,$matches)){

    if(isset($matches[1])) $request->parliament = $matches[1];
    if(isset($matches[2])) $request->session = $matches[2];
    $f_parl = 1;
    
  } else if(!$f_parl_id && preg_match("/^([0-9]{6,16})$/",$q,$matches)){
    
    $request->parl_id = $matches[1];
    $f_parl_id = 1;
    
  } else {
  
    notify_bad_arg("URI segment ".($i+2), $q, "Expected parliament session, bill number or parl.gc.ca id");
  
  }

}


//  Other request variables, such as sorting order
{

static $ok_if_in_array = array(
  "mode" => array("asc", "desc", "a", "d", "ascending", "descending"),
  "sort" => array("introduced", "updated", "type", "status", "number")
);

static $ok_if_positive_number_less_than = array(
  "n" => BILLS_MAX_ENTRIES_PER_REQUEST,
  "p" => 1000
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






// Begin constructing the query.
// Everything below should read from, rather than write to, the request.


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

$group_by = "group by bills.id";

$table = "bills";
$joins = array();

//  detail options

if($request->get_sponsor){

  $table = "bills_mps";
  
  $select = array_merge($select, array(
    "concat (mps.first_name,\" \",mps.last_name) as sponsor",
    "mps.party",
    "ridings.name as sponsor_riding",
  ));
  
  $joins = array(
    "join bills on bills.id = bills_mps.bill_id",
    "left join mps on mps.parl_id = bills_mps.sponsor_parl_id",
    "left join ridings_mps on mps.id = ridings_mps.mp_id",
    "left join ridings on ridings_mps.riding_id = ridings.id"
  );

}

if($request->get_summary){
      
  $select[] = "bill_summaries.summary_en";
  $select[] = "bill_summaries.summary_fr";
  
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
    
  $joins[] = "join `bills_subjects` on bills_subjects.bill_id = bills.id";
  $joins[] = "join `subjects` on bills_subjects.subject_id = subjects.id";

}

if($request->get_votes){

  $select[] = "(
    select 
    concat('{\"yes\":',sum(vote&1),',\"no\":',round(sum(vote&2)/2),',\"total\":',count(*),'}')
    from `user_votes`
    where bills.id = user_votes.bill_id
  ) as votes
  ";
  
}


// Construct the 'where' clause

$cond = array(
  "`parl_session`='{$request->parliament}-{$request->session}'",
  "`chamber`='{$request->chamber}'"
);

if($request->number) $cond[] = "`number`='{$request->number}'";
if($request->parl_id) $cond[] = "`bills`.`parl_id`='{$request->parl_id}'";



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
$cond = implode("&&",$cond);
$joins = implode(" ", $joins);


//  Query the database

$result = DB::query($query = "
  select {$select}  
  from {$table}
  {$joins}
  where {$cond}
  {$group_by}
  order by {$request->sort} {$request->mode}
  limit {$request->p},{$request->n}
");


$Response->n_results = $result->rowCount();

$Response->bills = array();

while($r = $result->fetchObject()){
  
  if(isset($r->sponsor)){
    $spons = lcname($r->sponsor);
    $r->sponsor_uri = "/mps/{$spons}/";
    $r->sponsor_img = "{$spons}-{$r->party}.jpg";
  }
  
  $r->object_uri = "/bills/{$r->parl_session}/{$r->number}/";
  $Response->bills[] = $r;
  
}

