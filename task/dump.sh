#!/bin/bash

mysqldump --defaults-file=~/democratize.ca/dmcprivate/dump.opt dmc_public\
 > ~/democratize.ca/sqldumps/"dmc-public-`date +%e-%m-%y`.sql";
echo "Dumped dmc_public to ~/democratize.ca/sqldumps/\
dmc-public-`date +%e-%m-%y`.sql.";



