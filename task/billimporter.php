<?php
require_once(__DIR__."/spoof.php");
require_once(__DIR__."/../base/codes.php");
require_once(__DIR__."/../base/db.php");

class BillImporter {
  
  private $index = array();
  private $bill_xml_url;

  function __construct($source, $savepath = "../var/"){

    $this->xml = null;
    $this->bill_xml_url = $source;
    $this->path = $savepath;
    $this->xmlpath = $savepath;
    $this->clock  = microtime(1);
    $this->result = 0;
    $this->download_time = 0;
    
  }  
  
    
  public function import_bills(){
  
    if(!$this->load_xml()) throw new Exception("Error loading bill xml!");
    
    $bills = array();
    $sponsors = array();
    $publications = array();
    $iteration_limit=1000;
    $i=0;
    
    foreach($this->xml->Bill as $Bill){
    
      //unset($Bill->Statute);                *         
      //unset($Bill->ComingIntoForce);        *     
      //unset($Bill->LegisInfoNotes);         *
      //unset($Bill->PrimeMinister);          *
      
      $bill = new StdClass;
      $sponsor = new StdClass;
      $pubs = array();
      
      $parl = (int)$Bill->ParliamentSession->attributes()->parliamentNumber;
      $sess = (int)$Bill->ParliamentSession->attributes()->sessionNumber;
      $chamber = (string)($Bill->BillNumber->attributes()->prefix);
      $number = (int)$Bill->BillNumber->attributes()->number;
      
      $bill->pscn = "{$parl}-{$sess}/{$chamber}-{$number}";
      $bill->number = $number;
      $bill->parl = $parl;
      $bill->sess = $sess;
      $bill->chamber = $chamber;
      
      
      
      // $bill->parl_session = "{$parl}-{$sess}";
      $bill->parl_id      = (int)$Bill->attributes()->id;
      $bill->introduced   = (string)$Bill->BillIntroducedDate;
      $bill->updated      = (string)$Bill->attributes()->lastUpdated;
      $bill->title_en    = (string)$Bill->BillTitle->Title[0]; 
      $bill->title_fr    = (string)$Bill->BillTitle->Title[1];
      $bill->short_title_en  = (string)$Bill->ShortTitle->Title[0];
      $bill->short_title_fr  = (string)$Bill->ShortTitle->Title[1];
      $bill->type         = substr(md5((string)$Bill->BillType->Title[0]),0,4);
      
      if(count($Bill->Events))
        $bill->status = substr(md5((string)$Bill->Events->attributes()->laagCurrentStage),0,6);
      else
        $bill->status = "";
      
      $bill->mp_alias = lcname((string)$Bill->SponsorAffiliation->Person->FullName);
      
      $bills[] = $bill;
    
    
      
      if(isset($Bill->Publications->Publication))
        foreach($Bill->Publications->Publication as $pube){
          $pub = new StdClass;
          $pub->title_en = (string)$pube->Title[0];
          $pub->title_fr = (string)$pube->Title[1];
          $pub->parl_id  = (int)intval($pube->attributes()->id);
          $pub->pscn  = $bill->pscn;
          $publications[]=$pub;
        }  
      
      if(++$i > $iteration_limit) 
        throw new Exception("Iteration limit exceeded in update loop.");      

    }
    
    echo "Starting transaction...\n";
    DB::get(1)->query("start transaction;");
    
    if(!$this->push("bill",$bills)) die(DB::get(1)->query("rollback;"));
    if(!$this->push("publication",$publications)) die(DB::get(1)->query("rollback;"));
    
    DB::get(1)->query("commit;");
    echo "Committed\n";

    return 1;
    
  }  
  
  private function push($table,$ins){

    if(!count($ins)){
      echo "Nothing to push to {$table}.\n";
      return 1;
    }
    echo "Pushing to $table...\n";    
  
    $insert = array();
    $keys = array();
    $d = 0;
    
    
    $conditions = array();
    
    $id = "pscn";
    
    foreach($ins as $in){
      
      $conditions[] = "{$id}='{$in->$id}'";
      
      foreach($in as $p=>$v){
        $keys[$p] = $p;
        $values[$p] = DB::get(1)->quote($v);
        
      }
      
      $insert[] = "(".implode(",", $values).")";
      
    }
    
    $condition = implode(" or ",$conditions);
    
    if(count($conditions)){
    
      echo "Deleting where {$id} = ".$conditions[0]." or ... ".$conditions[count($conditions)-1]." ...";
      $res = DB::get(1)->query("delete from `{$table}` where {$condition};");
    
      $d = $res->rowCount();
      
      if($d < 0){
        
        var_dump(DB::get(1)->errorInfo());
        return 0;
      
      } else echo (int)$d." affected rows.\nInserting...\n";
  
    }
    
    
    $res = DB::get(1)->query("insert into `{$table}` (".implode(",",$keys).") VALUES ".implode(",",$insert).";");
    
    var_dump(DB::get(1)->errorInfo());
    
    $n = count($ins);
    $numrows = $res->rowCount();
    
    if($n!=$numrows || $d > $n){
    
      DB::get(1)->query("rollback;");
      
      die("Couldn't push to $table (". 
        ($err?$err:"Tried $n, got $numrows")
      ."). Rolled back.\n\nImport failed!");
      
    } else {
      
      echo "$numrows/$n rows".($d?", $d replaced":"").". (".sprintf("%0.4fs",($this->clock=microtime(1)-$this->clock)).")\n\n";
      $this->clock = microtime(1);
    
      return 1;
    
    }
  
  
  }
  
  private function add_event($event){
    
    $i=0;
    foreach($event as $p=>$v){
      ++$i;
      $val[$p] = DB::get(1)->quote($v);
      $vll[]   = "$p={$val[$p]}";
    }
    
    DB::get(1)->query("INSERT INTO event (".implode(",",array_keys($val)).") VALUES (".implode(",",$val).");");

    // var_dump(DB::get(1)->errorInfo());
    
    return 1;

  }
  
  
  public function get_events(){
  
    if(!$this->load_xml()) throw new Exception("Error loading bill xml");
    
    $i = $result = 0;
    
    $events = array();
    
    foreach($this->xml->Bill as $Bill){
   
      $bill = new StdClass;
      
      $parl = (int)$Bill->ParliamentSession->attributes()->parliamentNumber;
      $sess = (int)$Bill->ParliamentSession->attributes()->sessionNumber;
      $chamber = (string)($Bill->BillNumber->attributes()->prefix);
      $number = (int)$Bill->BillNumber->attributes()->number;
      
      $bill->pscn = "{$parl}-{$sess}/{$chamber}-{$number}";
  
      foreach($Bill->Events->LegislativeEvents->Event as $e){
      
        $event = new StdClass;
        $event->parl_id = intval($e->attributes()->id);
        $event->chamber = (string)$e->attributes()->chamber;
        $event->date = date("Y-m-d H:i:s",strtotime($e->attributes()->date));
        $event->meeting_number = intval($e->attributes()->meetingNumber);
        $event->status = (string)$e->Status->Title[0];
          
        $committee = new StdClass;
        if($e->Committee->attributes()->id){
          $committee->parl_id = intval($e->Committee->attributes()->id);
          $committee->acronym = (string)$e->Committee->attributes()->accronym;
          $committee->title = new StdClass;
          $committee->title->en = (string)$e->Committee->Title[0];
          $committee->title->fr = (string)$e->Committee->Title[1];
          if(count($e->Committee->CommitteeMeetings->CommitteeMeeting)){
            foreach($e->Committee->CommitteeMeetings->CommitteeMeeting as $meeting){
              $meet = new StdClass;
              $meet->number   = intval($meeting->attributes()->number);
              $meet->time     = strtotime($meeting->attributes()->meetingDateTime);
              $meet->parl_id  = intval($meeting->attributes()->studyActivityId);
              $meets[] = $meet;
            }
            $committee->meetings = $meets;
          }
        } 

        $event->note = $e->Description->Title?(string)$e->Description->Title:"";
        $event->committee = json_encode($committee);
        $event->pscn  = $bill->pscn;


        $val = array();
        foreach($event as $p=>$v){
          $val[$p] = DB::get(1)->quote($v);
        }

        
        if(!$i++) 
          $fields = implode(",",array_keys($val));


        $events[] = "(".implode(",",$val).")";

        if(count($events)==256){
        
          $values = implode(",",$events);
          
          DB::get(1)->query("INSERT INTO event ({$fields}) VALUES {$values};");
          var_dump(DB::get(1)->errorInfo());
          $events = array();
        }

      } 
    }
  
    if(count($events)){
      $values = implode(",",$events);
      DB::get(1)->query("INSERT INTO event ({$fields}) VALUES {$values};");
      var_dump(DB::get(1)->errorInfo());
    }
    
  }
  
  public function officialidpump($echo=0){
 
    $i = 0;
    if($echo) echo "Checking for entries...<br/>";
    $result = DB::select("distinct sponsor FROM xbills");
    while($row = $result->fetch_object()){
      $s = json_decode($row->sponsor);
      $id = $s->parl_id;
      $a=strpos($s->name, " ");
      $name = substr($s->name,$a+1).", ".substr($s->name,0,$a);
      $spons[$id] = $name;
      $spons2[$name] = $id;
      $names[] = "name='$name'";
      if($echo){
        echo "$id ... ";
        
      }
      
    }
    if(!count($spons)) return false;
    
      $names = implode("||",$names);
      $r = DB::select("name, id from mps where officialid IS NULL && ({$names})");
    if($r->num_rows){
   
      if($echo) echo "<br/>Updating database...<br/>";
      if($echo) echo str_repeat("&nbsp;",$r->num_rows-1)."| 100%<br/>";
      
      while($mp = $r->fetch_object()){
        if($echo){
          echo "|";
      
        }
      
        if(!isset($spons2[$mp->name])) continue;
        
        $offid = $spons2[$mp->name];
        $id = $mp->id;
        DB::query("UPDATE mps SET officialid=$offid WHERE id=$id LIMIT 1;");
      
        $i+=DB::affrows();
      } 

    } else {
    
      if($echo) echo "<br/>No action required: tables are up-to-date.<br/>";
   
    }
    
    $this->clock = microtime(1) - $this->clock;
    $record = json_encode(array("time"=>time(),"updated"=>$i,"clock"=>$this->clock));
    file_put_contents($this->path."logs/officialidpump.log",$record,FILE_APPEND);
 
    if($i){
      if($echo) echo "<br/>Checking portraits...<br/>";
      $a = $this->mp_photocopy($echo);
      if(count($a)!=2){
        if($echo) echo "Something went wrong. Aborting.<br/>";
      } else {
        $was = $a[0]==1 ? "was":"were";
        if($echo) echo "{$a[0]} $was missing: {$a[1]} updated.<br/>";
      }
    }
    return $i;
    
  }
  
  private function mp_photocopy($echo=1){

    $data = DB::select("
      distinct xbills.sponsor_parl_id as parl_id, mps.image
      from mps
      inner join xbills
        on mps.officialid = xbills.sponsor_parl_id
      ")->getobjarray("parl_id", "image");

    $path = $this->path."../../img.democratize.ca/mp/";
    $missing = 0;
    $updated = 0;
    foreach($data as $id=>$image){
      if(file_exists($path.$id.".jpg")) continue;
      $missing++;
      if($echo){
        echo "$image -> $id.jpg <br/>";
        
      }
      if(!file_exists($path.$image)){
        
        if($echo) echo "<u style='background:#f00;color:white;'>Error: $image not found!</u> Copy failed.<br/>";
    
      } else {
      
        $img = file_get_contents($path.$image);
        $updated += (bool)file_put_contents($path.$id.".jpg", $img);
       
      }
    }
    return array($missing, $updated);
    
  }
  
  
  public static function exec_history($dir = "../var/", $dateformat=0){
  
    $h = opendir($dir);
    $record = array();
    
    if($h){
      while(($f=readdir($h))!==false){
        if(is_dir($dir.$f)) continue;
        if(!preg_match("/xml$/i",$f)) continue;
        $stat = stat($dir.$f);

        $record[] = $stat[9];
      }
      closedir($h);

    }
    rsort($record);
    if($dateformat)
      foreach($record as &$d)
        $d = date($dateformat, $d);
    
    return $record;
  
  }
  
  private function load_xml(){
    
    if($this->xml) return true;
    
    $today = intval(time()/60/60/24);
    $filename = $this->xmlpath.substr(md5($this->bill_xml_url),0,5)."_{$today}.xml";
    
    if(is_file($filename)){

      echo "Reading from file.\n";
      $xmlobject = simplexml_load_file($filename);
      $this->download_time = 0;

    } else {
      
      echo "Downloading from {$this->bill_xml_url}...\n";
      $curl = new Spoof($this->bill_xml_url);
      echo "Download complete, saved to {$filename} (".sprintf("%0.4fs",microtime(1)-$this->clock).")\n";
      file_put_contents($filename, $curl->data);
      $xmlobject = simplexml_load_string($curl->data);
      
      
    }  
    if(!$xmlobject) return false;
    
    $this->xml = $xmlobject;
    return 1;   
    
  }
  
}

  

