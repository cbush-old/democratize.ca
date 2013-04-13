<?php

//  Put local-specific defines here
if(file_exists(dirname(__FILE__)."/config.local.php"))
  include dirname(__FILE__)."/config.local.php";

//  Credentials for read-only access to the `dmc_public` database.
//  Your IP is probably locked out. Contact me for access
//  or download a copy of the database onto your local server
//  and change this information accordingly.

if(!defined("DB_PUB_DSN")) 
  define("DB_PUB_DSN", "mysql:dbname=dmc_public;host=mysql.democratize.ca");

if(!defined("DB_READ_USER")) define("DB_READ_USER", "dmc_reader");
if(!defined("DB_READ_PASS")) define("DB_READ_PASS", "Antidisestablishmentarian");

// Define these in your config.local.php
if(!defined("DB_WRITE_USER")) define("DB_WRITE_USER", "dmc_writer");
if(!defined("DB_WRITE_PASS")) define("DB_WRITE_PASS", "??????????");



//  Limit the number of bills per request (set by n=___ in the query string)
if(!defined("BILLS_MAX_ENTRIES_PER_REQUEST"))
  define("BILLS_MAX_ENTRIES_PER_REQUEST", 100);


//  Base URL
if(!defined("BASE_URL")) 
  define("BASE_URL","http://localhost");

//  Public root
if(!defined("PUBLIC_ROOT"))
  define("PUBLIC_ROOT","");

