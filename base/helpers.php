<?php

function getclean($index){
  
  return preg_replace("/[^a-z]/","",strtolower($_GET[$index]));

}
