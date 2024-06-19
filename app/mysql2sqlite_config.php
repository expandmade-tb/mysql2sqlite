<?php

$db_host='localhost'; // hostname to be used for the mysql db
$db_name='your_database'; // mysql database name
$db_user='your_user'; // mysql database user
$db_password='your_password'; // mysql database password of the given user

$sqlite_db="/storage/$db_name.sqlite"; // name and location of sqlite database

$use_transactions=true; // use transaction processing for all number of rows defined in $row_chunks)
$row_chunks=500; // how many rows should be read and written at once
$on_exception_stop=true; // stops the script after an exception
$empty_is_null=false; // an empty string value will be treated as NULL and not written to the database
