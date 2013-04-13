<?php
class Spoof {

	public function __construct($url = ""){
		$this->headers = array(
			"Accept-Language: en-us",
			"User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)",
			"Connection: Keep-Alive",
			"Cache-Control: no-cache"
			);
		$this->referer = 'http://www.google.ca/';
		$this->url = $url;
		if($url) $this->exec();
    
	}
  
	public function exec($url = ""){
    if($url)
      $curl = curl_init($url);
    else 
      $curl = curl_init($this->url);
    
		curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers); 
		curl_setopt($curl, CURLOPT_REFERER, $this->referer);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$this->data = curl_exec($curl);
		curl_close($curl);	
    
	}
  
}
