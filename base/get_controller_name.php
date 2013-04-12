<?php


//  Parse a URI and return the name of the appropriate controller,
//  or throw an HTTP_status exception


function get_controller_name($URI){


  $method = strtolower(request_method());
  
  //  The function RETURNS or THROWS after each of the 
  //  following possibilities!
  
  //  Possibility 1: home page. No URI - accept GET only.
  
  if(count($URI)==0){ // home
  
    if($method!="get")
      throw new HTTP_status (405, array("Allow"=>"GET"));

    return "get.home";
    
  }


  $base = $URI[0];
  
  //  Possibility 2: An actual controller that isn't the bill loader
  //  Acceptable URI segments are the keys - their allowed methods
  //  are the values
  
  static $ok_base = array(
    "about"     => "GET",
    "comment"   => "GET, POST, PUT, DELETE",
    "contribute"=> "GET",   
    "fail"      => "GET", // test
    "license"   => "GET",
    "privacy"   => "GET",
    "rss"       => "GET",
    "user"      => "GET, POST, PUT, DELETE",
    "vote"      => "GET, POST, PUT, DELETE"
  );

  if(isset($ok_base[$base])){
    
    $allow = $ok_base[$base]; 
    
    if(stripos($allow,$method)===false)
      throw new HTTP_status (405, array("Allow" => $allow));
  
    return "{$method}.{$base}";
    
  }
  
  
  //  Possibility 3: a bill request in the URI
  //  Allow GET only - for now?
  
  //  This function gets an array of regexes, see helpers
  $rx = bill_uri_regexes();
  
  //  If none of the possible URI segments as defined 
  //  in bill_uri_regexes() are found, try allowing it
  //  as a possible subject name
  //  THIS POSSIBILITY MAY CLOBBER OTHER POSSIBILITIES
  //  IF THE ORDER IS CHANGED
  $rx["subject_maybe"] = "([a-z-]{3,})";
  
  $patterns = implode("|",$rx);
  
  if(preg_match("/^({$patterns})$/", $base)){
   
    if($method != "get")
      throw new HTTP_status (405, array("Allow" => "GET"));
  
    return "{$method}.bills";
    
  }
  
  

  //  Possibility 4: none of the above
  
  throw new HTTP_status (404);


}
