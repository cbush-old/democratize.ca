<?php
function get_bill_code($key){

  static $BILLTYPES = array(
    "Private Member’s Bill"=>"pmb",
    "Senate Government Bill"=>"sgb",
    "House Government Bill"=>"hgb",
    "Senate Public Bill"=>"spu",
    "Senate Private Bill"=>"spr",
    "HouseAt1stReading"=>"h1r",
    "HouseAt2ndReading"=>"h2r",
    "HouseAt3rdReading"=>"h3r",
    "HouseAtReportStage"=>"hrs",
    "HouseInCommittee"=>"hic",
    "RoyalAssentGiven"=>"rag",
    "SenateAt2ndReading"=>"s2r",
    "SenateInCommittee"=>"sic",
    "WillNotBeProceededWith"=>"npw",
    "BillNotActive"=>"bna",
    "BillDefeated"=>"bdf",
  );

  if(isset($BILLTYPES[$key])) 
    return $BILLTYPES[$key];
  echo "couldn't find ".(string)$key."\n";

}

function billcode_text($key){

  static $BILLTYPES = array(
    "pmb"=>"Private Member&#39;s Bill",
    "sgb"=>"Senate Government Bill",
    "hgb"=>"House Government Bill",
    "spu"=>"Senate Public Bill",
    "spr"=>"Senate Private Bill",
    "h1r"=>"House at 1st Reading",
    "h2r"=>"House at 2nd Reading",
    "h3r"=>"House at 3rd Reading",
    "hrs"=>"House at Report Stage",
    "hic"=>"House in Committee",
    "rag"=>"Royal Assent Given",
    "s2r"=>"Senate at 2nd Reading",
    "sic"=>"Senate in Committee",
    "npw"=>"Will Not Be Proceeded With",
    "bna"=>"Bill Not Active",
    "bdf"=>"Bill Defeated"
  );

  return isset($BILLTYPES[$key])? $BILLTYPES[$key] : "Status Unknown";

}

function get_bill_id($parl, $sess, $haus, $numb){

  $haus = strtolower($haus)=="c"?0:1;
  return ($parl<<20) + ($sess<<16) + ($numb<<1)  + ($haus);
  
}

function decode_bill_id($code){
  $parl = ($code&0xfff00000)>>20;
  $sess = ($code&0x000f0000)>>16;
  $numb = ($code&0x0000fffe)>>1;
  $haus = ($code&0x00000001);
  if($parl < 0) $parl+=4096;
  
  return "{$parl}-{$sess} ".($haus?"S":"C")."-{$numb}";

}

function lcname($in){
  return 
    preg_replace("/[^a-z-0-9]/","",
      strtolower(
        str_replace(array(" ",".","/"),"-",
          str_replace(". ","-",
            iconv("UTF-8", "ASCII//TRANSLIT", $in)
          )
        )
      )
    );
}

function hash32($in){
  return substr(md5($in),0,8);
}

function dmchash($in){
  return intval(hexdec(substr(md5(lcname($in)),0,8)));
}

function provcode($p){

  static $provs = array(
    "Alberta"=>"ab",
    "Ontario"=>"on",
    "Nunavut"=>"nv",
    "British Columbia"=>"bc",
    "New Brunswick"=>"nb",
    "Saskatchewan"=>"sk",
    "Newfoundland and Labrador"=>"nl",
    "Nova Scotia"=>"ns",
    "Manitoba"=>"mb",
    "Québec"=>"pq",
    "Northwest Territories"=>"nwt",
    "Prince Edward Island"=>"pei",
    "Yukon"=>"yk"
  );
  return isset($provs[$p])? $provs[$p]:"";
  
}
