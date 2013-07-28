<?php

/**
 *	Code that is executed which is common to ALL PHP scripts, regardless
 *	as to whether they are CLI- or web-invoked.
**/


// Load important constants used in PHP scripts all throughout the site.
// This should be the very first thing included.
require_once "defines.php";

// Make sure defines.php is filled out
if (!WEBMASTER_EMAIL || !SITE_DOMAIN || !DB_PRODUCTION_HOST || !DB_PRODUCTION_USERNAME
	|| !DB_PRODUCTION_PASSWORD || !DB_DEV_HOST || !DB_DEV_USERNAME
	|| !SMARTYSTREETS_HTML_KEY || !SMS_API_KEY || !SMS_API_SECRET)
{
	die("Please fully configure defines.php. Aborting.");
}

// Include important functions used throughout the site
require_once DOCROOT."/lib/functions.php";

// Prepare to auto-include needed class/behavioral files.
set_include_path(DOCROOT."/lib/classes");

// Register the autoload function which will now loads class files as needed
spl_autoload_register("autoload");

// Set the current timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Establish proper error reporting
if (ENV == PRODUCTION)
{
	// Log all errors and warnings and notices, also specify custom error handler to send email to webmaster.
	// On DigitalOcean, our comprehensive PHP process log is: /var/log/php5-fpm.log

	// Report and display all types of errors
	ini_set('error_reporting', E_ALL);
	ini_set('display_errors', 0);
	ini_set('log_errors', 1);

	// Tell PHP to use our function to handle most errors (doesn't handle E_PARSE and some others).
	// This function should email the webmaster about production issues, for convenience.
	set_error_handler("errorHandler");
}
else	// DEV
{
	ini_set('error_reporting', E_ALL);
	ini_set('display_errors', 1);
	ini_set('log_errors', 0);
}


?>