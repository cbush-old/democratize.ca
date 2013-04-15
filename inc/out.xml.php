<?php

header("Content-type:text/xml;charset=utf-8");

$doc = new DOMDocument('1.0', 'utf-8');

function xml_encode(&$node, $k, $v){//, $v){
  
  global $doc;
  
  $n = $doc->createElement("{$k}");
  
  if(is_object($v)||is_array($v)){
  
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
  
    $n->appendChild($doc->createTextNode($v));
  
  }
  
  $node->appendChild($n);
  
}


xml_encode($doc, "Response", $Response);

echo $doc->saveXML();
