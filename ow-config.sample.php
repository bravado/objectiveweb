<?php
/**
 * 
 * Objectiveweb config
 *
 * User: guigouz
 * Date: 08/04/11
 * Time: 00:35
 */


// THIS IS JUST A SAMPLE FILE
// THE REAL ow-config.php MUST RESIDE ON objectiveweb's PARENT
// DIRECTORY (../ow-config.php from here)

define('MYSQL_HOST', '127.0.0.1');
define('MYSQL_USER', 'root');
define('MYSQL_PASS', 'root');
define('MYSQL_DB', 'objectiveweb');

// Enable debugging (logs queries to error_log)
define('DEBUG', 1);

// Security parameters

// A random string. Make it as random as possible and keep it secure.
// This is a crypthographic key that uLogin will use to generate some data
// and later verify its identity.
// The longer the better, should be 40+ characters.
// Once set and your site is live, do not change this.
define('OW_SITE_KEY', 'Change me !!!');

// App registration

// Load apps i.e. to enable the "skeleton" app:
//register_app('skeleton');