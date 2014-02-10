<?php

// TODO: change user names and passwords back to "CHANGE ME!!!"
// TODO: rename dbname back to ulogin

// ------------------------------------------------
//	DATABASE ACCESS
// ------------------------------------------------

// Connection string to use for connecting to a PDO database.
define('UL_PDO_CON_STRING', 'mysql:host='.MYSQL_HOST.';dbname='.MYSQL_DB);
// Example for SQLite: 
//define('UL_PDO_CON_STRING', 'sqlite:/path/to/db.sqlite');

// table prefix support
define('UL_TABLE_PREFIX', 'ow_');

// store logins table as access request objects
define('UL_LOGINS', 'ow_aro');

// SQL query to execute at the start of each PDO connection.
// For example, "SET NAMES 'UTF8'" if your database engine supports it.
// Unused if empty.
define('UL_PDO_CON_INIT_QUERY', "");

// ------------------------------------------------
//	DATABASE USERS
// ------------------------------------------------

// Following database users should only have access to their specified table(s).
// Optimally, no other user should have access to the same tables, except
// where listed otherwise.

// If you do not want to create all the different users, you can of course
// create just one with appropriate credentials and supply the same username and password
// to all the following fields. However, that is not recommended. You should at least have
// a separate user for the AUTH user.

// You do not need to set logins for functionality that you do not use
// (for example, if you use a different user database).

// AUTH
// Used to log users in.
// Database user with SELECT access to the
// logins table.
define('UL_PDO_AUTH_USER', MYSQL_USER);
define('UL_PDO_AUTH_PWD', MYSQL_PASS);

// LOGIN UPDATE
// Used to add new and modify login data.
// Database user with SELECT, UPDATE and INSERT access to the
// logins table.
define('UL_PDO_UPDATE_USER', MYSQL_USER);
define('UL_PDO_UPDATE_PWD', MYSQL_PASS);

// LOGIN DELETE
// Used to remove logins.
// Database user with SELECT and DELETE access to the
// logins table
define('UL_PDO_DELETE_USER', MYSQL_USER);
define('UL_PDO_DELETE_PWD', MYSQL_PASS);

// SESSION
// Database user with SELECT, UPDATE and DELETE permissions to the
// sessions and nonces tables.
define('UL_PDO_SESSIONS_USER', MYSQL_USER);
define('UL_PDO_SESSIONS_PWD', MYSQL_PASS);

// LOG
// Used to log events and analyze previous activity.
// Database user with SELECT, INSERT and DELETE access to the
// logins-log table.
define('UL_PDO_LOG_USER', MYSQL_USER);
define('UL_PDO_LOG_PWD', MYSQL_PASS);
