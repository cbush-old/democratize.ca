<?php

//  Put local-specific defines here
if(file_exists(dirname(__FILE__)."/config.local.php"))
  include dirname(__FILE__)."/config.local.php";

//  Credentials for read-only access to the `dmc_public` database.
//  Your IP is probably locked out. Contact me for access
//  or download a copy of the database onto your local server
//  and change this information accordingly.

if(!defined("DB_DSN")) define("DB_DSN", "mysql:dbname=dmc_public;host=mysql.democratize.ca");
if(!defined("DB_USER")) define("DB_USER", "dmc_reader");
if(!defined("DB_PASS")) define("DB_PASS", "Antidisestablishmentarian");


//  Limit the number of bills per request (set by n=___ in the query string)
if(!defined("BILLS_MAX_ENTRIES_PER_REQUEST"))
  define("BILLS_MAX_ENTRIES_PER_REQUEST", 100);


//  Base URL
if(!defined("BASE_URL")) 
  define("BASE_URL","http://localhost");

