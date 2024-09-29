# A PHP script to migrate a mysql database to a sqlite database
The idea of this script was to create a sqlite database for a wordpress installation which uses sqlite instead of mysql (https://github.com/aaemnnosttv/wp-sqlite-db).

Of course you can also migrate other mysql databases to sqlite. 

## How to use

Extract the archive and open the configuration file '**mysql2sqlite_config.php**' with an editor and modify the database connection parameters to your mysql database. 

```
$db_host='localhost'; // hostname to be used for the mysql db
$db_name='your_database'; // mysql database name
$db_user='your_user'; // mysql database user
$db_password='your_password'; // mysql database password of the given user
$sqlite_db="/storage/$db_name.sqlite"; // name and location of sqlite database
$use_transactions=true; // use transaction processing for all number of rows defined in $row_chunks)
$row_chunks=500; // how many rows should be read and written at once
$on_exception_stop=true; // stops the script after an exception
$empty_is_null=false; // an empty string value will be treated as NULL and not written to the database
```

run the script in the app folder:

    php -f mysql2sqlite.php

or with execution permission:

```
./mysql2sqlite.php
```

The script will create a sqlite database at the location given in the 'sqlite_db' parameter and transfer all tables from your mysql to that new database.

If something went wrong, you will have to remove the sqlite database manually. The script will not delete the database, drop tables or delete rows from a table.

To overwrite the configuration file you can use the following command line parameters:

```
   -h host
   -d database
   -u user
   -p password 
```

## To keep in mind

The script will transfer tables, indexes, foreign keys and constraints. It will NOT transfer trigger, stored procedures, views etc.

If you are running the script on the Webserver make sure the created database is NOT created in the public folder. 

