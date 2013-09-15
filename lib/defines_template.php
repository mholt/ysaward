<?php

/**
 *	This file contains important definitions of constants used by both the web and
 *	CLI binaries of PHP. It's vital that this file is kept updated and secured, as it
 *	contains authentication credentials and other private details for site functionality.
**/



/*
	VITALS / BASICS
*/

// Absolute path to the site's root folder (like "public_html" or "www") on a dev machine.
// Used as a fallback for DOCROOT if $_SERVER['DOCUMENT_ROOT'] is not available.
// This should NOT be the same path as it is in production.
define('DOCROOT_DEV', "");		// example: "/Users/matt/Sites/ysasite"

// Absolute path to the site's root folder on the production server.
// Used as a fallback for DOCROOT if $_SERVER['DOCUMENT_ROOT'] is not available.
// This should NOT be the same path as it is in development.
define('DOCROOT_PRODUCTION', "");	// example: "/home/ysa_site/public_html"

// Webmaster's email address (for site- and technical-related notifications)
// ** This NEEDS to be an active inbox that is regularly checked. **
define('WEBMASTER_EMAIL', "");

// Webmaster's name
define('WEBMASTER_NAME', "");

// Email address for support or site-related issues, from users of the site.
// Will be HTML-encoded then displayed in the footer of each page.
// ** This NEEDS to be an active inbox that is regularly checked. **
define('YSA_EMAIL', "");

// Full URL to Trello board related to site development/progress.
// See www.trello.com -- use this to track development of this specific
// site, not the YSA site code altogether.
define('TRELLO_BOARD', "");		// example: https://trello.com/board/.../...

// Domain name the site is served from (without "www." or any other extra stuff)
define('SITE_DOMAIN', "");		// Example: "ysaward.com"

// Name of the site (could be the full name of the main unit, like "Provo Utah YSA 2nd Stake")
define('SITE_NAME', "");

// Short name of the site (could be the abbreviated unit name, like "YSA 2nd Stake")
define('SHORT_SITE_NAME', "");

// Path to the main logo used on the home/registration pages; put site-custom resources in a "custom/" folder
define('SITE_LARGE_IMG', "");		// Example: "/images/custom/ysa-large.png"

// Path to the smaller logo displayed on all other pages; put site-custom resources in a "custom/" folder
define('SITE_SMALL_IMG', "");		// Example: "/images/custom/sa-small.png"

// Path to image, same size as the one above, when the main logo is hovered over; put site-custom resources in a "custom/" folder
define('SITE_SMALL_IMG_HOVER', "");		// Example: "/images/custom/ysa-small-hover.png"

// Google Analytics tracking ID
define('ANALYTICS_TRACKING_ID', "");	// Example: "UA-12345678-1"


/*
	PRODUCTION DATABASE
*/

// Database hostname on production server (usually "localhost")
define('DB_PRODUCTION_HOST', "");

// Database login username on production server
define('DB_PRODUCTION_USERNAME', "");

// Database login password on production server
define('DB_PRODUCTION_PASSWORD', "");

// Name of the database on production server
define('DB_PRODUCTION_SCHEMA', "");




/*
	DEVELOPMENT DATABASE
*/

// Database hostname on dev server (usually "localhost" or "127.0.0.1")
define('DB_DEV_HOST', "");

// Database login username on dev server
define('DB_DEV_USERNAME', "");

// Database login password on dev server
define('DB_DEV_PASSWORD', "");

// Name of the database on dev server
define('DB_DEV_SCHEMA', "");








/*
	EMAIL (INTERNAL, AMAZON SES, AND DKIM)
*/


// The default black-hole email address
define('EMAIL_BLACKHOLE', "");		// example: no-reply@...

// The default "From" address when emails are sent by members
define('EMAIL_FROM', "");			// example: website@...

// Maximum number of recipients per email message for regular-privileged users
define('EMAIL_MAX_RECIPIENTS', 5);	// typically 5 is good



// The SMTP host to use when sending emails
define('SMTP_HOST', "");

// The Amazon AWS IAM username for SES
define('AWS_IAM_USERNAME', "");

// The Amazon AWS IAM password for SES
define('AWS_IAM_PASSWORD', "");



// The DKIM domain name
define('DKIM_DOMAIN', "");		// example: ysaward.com; whatever domain your site is served on

// Path to the DKIM private key file
define('DKIM_PRIVATE_KEY', "");

// The DKIM selector, as found in the DNS record as a TXT entry
define('DKIM_SELECTOR', "");

// The password for the DKIM private key
define('DKIM_PASSPHRASE', "");

// Internal password required to send mass emails using an EmailJob
define('EMAIL_JOB_PASSWORD', "");	// you make this up; doesn't have to be fancy



/*
	SMS / TEXTING (NEXMO)
*/

// The base URL for requests about the account (metadata)
define('SMS_META_BASE', "https://rest.nexmo.com");

// The base URL for API requests to send the actual texts
define('SMS_API_BASE', "https://rest.nexmo.com/sms/json");

// API key (not necessarily secret) on our account
define('SMS_API_KEY', "");

// API secret (like a password)
define('SMS_API_SECRET', "");

// Milliseconds between messages (US limits: 1 per second per number, and 500/day from a number)
define('SMS_MS_BETWEEN_MESSAGES', 1000);

// Arbitrary limit we set on the number of texts a regular user can send in one day
define('SMS_MAX_PER_DAY', 5);		// typically 5 is good

// Maximum number of characters per text message (if it fits into one piece)
define('SMS_CHARS_PER_TEXT', 160);	// should be 160

// Number of characters of overhead used if a text has to split into more than one piece
define('SMS_SEGMENT_OVERHEAD', 7);	// should be 7

// Password required to send texts using an SMSJob
define('SMS_JOB_PASSWORD', "");		// you make this up; doesn't have to be fancy




/*
	ADDRESS VERIFICATION (SmartyStreets)
	Non-profits get it free: http://smartystreets.com/free-address-verification
*/

// An HTML key from your account that's tied to the domain of the site
// (Make sure to get a free subscription for non-profits; the company is local to Provo)
define('SMARTYSTREETS_HTML_KEY', "");









/*
	PRIVILEGE IDs
	These correspond to the privileges found in the database,
	and using the constants make it more self-documenting
	than a plain-ol number. See the database for documentation.
	... probably shouldn't change these unless you know what you're doing.
*/

define('PRIV_EMAIL_ALL',		1);
define('PRIV_EMAIL_BRO',		2);
define('PRIV_EMAIL_SIS',		3);
define('PRIV_EXPORT_EMAIL',		4);
define('PRIV_EXPORT_PHONE',		5);
define('PRIV_EXPORT_BDATE',		6);
define('PRIV_MNG_FHE',			7);
define('PRIV_MNG_SURVEY_QU',	8);
define('PRIV_MNG_SURVEY_PER',	9);
define('PRIV_MNG_SITE_PRIV',	10);
define('PRIV_MNG_CALLINGS',		11);
define('PRIV_MNG_PROFILE_PICS',	12);
define('PRIV_DELETE_ACCTS',		13);
define('PRIV_TEXT_ALL',			14);
define('PRIV_TEXT_FHE',			15);




/*
	ERROR HANDLING (PRODUCTION)
*/

// The "From" name when emails are sent to the webmaster about PHP errors
define('ERR_HANDLE_FROM_NAME', "");		// example: "php5-fpm"

// The path to a log file; or leave empty/blank to use the default 
define('LOG_FILE', "error_log");		// typically "error_log" is conventional

// For use in error_log(); 3 means to append to a log if already present (set to 0 for default instead)
define('LOG_TYPE', 3);					// usually 3








/*
	MISCELLANEOUS
*/


// Default timezone, in case php.ini isn't configured properly
define('DEFAULT_TIMEZONE', "America/Denver"); 	// See: http://php.net/manual/en/timezones.php

// The file path of the currently-executing script
define('PATH', isset($_SERVER['PHP_SELF']) && strlen($_SERVER['PHP_SELF']) > 1 ? $_SERVER['PHP_SELF'] : "");

// The current domain name (hostname)
define('HOSTNAME', isset($_SERVER['SERVER_NAME']) && strlen($_SERVER['SERVER_NAME']) > 1 ? $_SERVER['SERVER_NAME'] : "");







/*
	FINAL DOCROOT DEFINITION
	Make a good guess as to whether we're on production or development.
	Relies on the HOSTNAME, DOCROOT_DEV, and DOCROOT_PRODUCTION defines above.

	After this, you can see if ENV == PRODUCTION or ENV == DEV
	to know if we're on production or development, respectively.

	You don't have to change any of this unless the production site's domain
	is not a .org but is on some other TLD instead.
*/


define('PRODUCTION', "Production");
define('DEV', "Development");

if (HOSTNAME && strpos(HOSTNAME, ".dev") === false)
	define('ENV', PRODUCTION);
else
{
	// We can't immediately assume development in this case;
	// we might be in CLI mode, which doesn't have $_SERVER.
	if (file_exists(DOCROOT_PRODUCTION))
		define('ENV', PRODUCTION);
	else
		define('ENV', DEV);
}

// Now define the DOCROOT constant for convenience.
// Relies on several constants defined above. Establishes the site's root path
// even if $_SERVER['DOCUMENT_ROOT'] is not available (like when executed from command line).
define('DOCROOT', isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT']
					? $_SERVER['DOCUMENT_ROOT']
					: (ENV == PRODUCTION ? DOCROOT_PRODUCTION : DOCROOT_DEV));

?>