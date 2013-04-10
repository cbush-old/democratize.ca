<script>

</script>
<style>
td {
  border:1px solid black;
}
</style>
<body style='font-family:sans-serif;font-size:10pt;'>

<?php

function unravel($x){

  $str = "";

  if(is_object($x) || is_array($x)){
    $str .= "<table>";
    foreach($x as $k=>$v){
      if(strpos($k,"_uri") && !is_object($v) && !is_array($v))
        $v = "<a href='".url_from_uri($v)."'>{$v}</a>";

      else if(strpos($k,"_img"))
        $v = "<img src='http://img.democratize.ca/portraits/{$v}'/>";
      
      $str .= "<tr><td>{$k}</td><td>";
      $str .= unravel($v);
      $str .= "</td></tr>";
    }
    $str .= "</table>";
  } else {
  
    $str .= $x;

  }

  return $str;

}


echo unravel($Response);

