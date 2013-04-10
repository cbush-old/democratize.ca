<?php

//  Credentials for read-only access to the `dmc_public` database.
//  Your IP is probably locked out. Contact me for access
//  or download a copy of the database onto your local server
//  and change this information accordingly.

define("DB_DSN", "mysql:dbname=dmc_public;host=mysql.democratize.ca");
define("DB_USER", "dmc_reader");
define("DB_PASS", "Antidisestablishmentarian");


//  Limit the number of bills per request (set by n=___ in the query string)
define("BILLS_MAX_ENTRIES_PER_REQUEST", 100);


//  Base URL
define("URL","http://localhost/dmc/public");
