<?php

header("Content-type:text/xml");

$doc = new DOMDocument('1.0', 'iso-8859-1');


function xml_encode(&$node, $k, $v){
  
  global $doc;
  
  if(is_object($v)||is_array($v)){
  
    $n = $doc->createElement($k);
    
    foreach($v as $kk=>$vv){
      if(is_numeric($kk)){
        
        $singular_k = substr($k,-1)=="s"?
          substr($k,0,strlen($k)-1):"{$k}Inst";
          
        if(!strlen($singular_k)) $singular_k = "{$k}Inst";
        
        xml_encode($n, $singular_k, $vv);
        
      } else {
      
        xml_encode($n, $kk, $vv);
        
      }
      
    }
  } else {
  
    $n = $doc->createElement($k, $v);
  
  }

  $node->appendChild($n);
  
}


xml_encode($doc, "Response", $Response);

echo $doc->saveXML();


