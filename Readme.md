# A PHP script to migrate a mysql database to a sqlite database
The idea of this script was to create a sqlite database for a wordpress installation which uses sqlite instead of mysql (https://github.com/aaemnnosttv/wp-sqlite-db).

Of course you can also migrate other mysql databases to sqlite. 

## How to use

Extract the archive and open the configuration file 'mysql2sqlite_config.php' with an editor and modify the database connection parameters to your mysql database. 

run the script in the app folder:

    php -f mysql2sqlite.php

the script will, hopefully create a sqlite database at the location given in the 'sqlite_db' parameter and transfer all tables from your mysql to that new database.

If something whent wrong, you will have to remove the sqlite database manually. The script will not delte the database, drop tables or delete rows from a table.

## To keep in mind

The script will transfer tables, indexes, foreign keys and constraints. It will NOT transfer trigger, stored procedures, views etc.

If you are running the script on the Webserver make sure the created database is NOT created in the public folder. 

