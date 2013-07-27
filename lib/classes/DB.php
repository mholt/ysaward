<?php

/**
 * @author Matthew Holt
 */

class DB
{
	private $Resource = null;

	public function __construct()
	{
		// First try connecting to production (for performance); otherwise try dev.
		// As usual, these constants are defined in defines.php.
		$this->Resource = @mysql_pconnect(DB_PRODUCTION_HOST, DB_PRODUCTION_USERNAME, DB_PRODUCTION_PASSWORD);
		$db = DB_PRODUCTION_SCHEMA;

		if (!$this->Resource)
		{
			// Connecting to production failed; try dev settings
			$this->Resource = @mysql_pconnect(DB_DEV_HOST, DB_DEV_USERNAME, DB_DEV_PASSWORD);
			$db = DB_DEV_SCHEMA;
		}

		if (!$this->Resource)
			fail("Error connecting to database: ".mysql_error());
		if (!mysql_query("SET NAMES 'UTF8'"))	// security measure
			fail("Error switching to UTF-8 charset: ".mysql_error());
		if (!mysql_select_db($db))
			fail("Error selecting db: ".mysql_error());
		if (!mysql_query("SET SESSION sql_mode=''"))		// Very important; see bottom of this file for a thorough explanation for this
			fail("Error setting sql_mode: ".mysql_error());
	}

	/**
	 * Run a query. PLEASE sanitize inputs BEFORE this...
	 * (BuildSaveQuery function does sanitize already...
	 * but you gotta do everything else when the occassion arises.)
	 * (You can use DB::Safe() to make a value safe to insert)
	 * @var $query The query to run.
	 * @return The query result.
	 */
	public static function Run($query)
	{
		$r = mysql_query($query);
		if (!$r)
			fail("ERROR EXECUTING QUERY: ".mysql_error());
		return $r;
	}

	/**
	 * Builds a MySQL query to save or update an object. Only useful for
	 * classes that mirror a database table, such as the "Member"
	 * or "Calling" classes.
	 * @var $object = The instance we're saving for
	 * @var $vars = An associative array of all necessary fields ("class properties")
	 * @var $stripTags = If true, tags will be stripped from content. If false, they are left intact.
	 * @var $tableSuffix = Example: If the class is "Member", the table is "Members", so the suffix is 's'.
	 * @return The query string to save the object
	**/
	public static function BuildSaveQuery($object, $vars, $stripTags = true, $tableSuffix = 's')
	{
		$tableName = get_class($object).$tableSuffix;
		$fields = "";
		$values = "";
		$update = "";

		foreach ($vars as $key => $value)
		{
			if (is_array($value))
				$value = json_encode($value);
			$value = DB::Safe($value, $stripTags);
			if ($value)
			{
				$fields .= "{$key}, ";
				$values .= "'{$value}', ";
			}
			$update .= "{$key}='{$value}', ";
		}

		// Trim trailing commas
		$fields = substr($fields, 0, strlen($fields) - 2);
		$values = substr($values, 0, strlen($values) - 2);
		$update = substr($update, 0, strlen($update) - 2);
		
		return "INSERT INTO {$tableName} ({$fields}) VALUES ({$values}) ON DUPLICATE KEY UPDATE {$update}";
	}


	/**
	 * Since Credentials are stored in a separate table but loaded inline into the same object,
	 * here we extract the Credentials information so BuildSaveQuery (above) can save the object
	 * without hassle. That function shouldn't encounter a member field that isn't in the table.
	 * @var $obj 	= The Member or StakeLeader for which to save credentials
	 * @var &$vars 	= A reference to the object's member variables, so they can be altered directly
	 **/
	public static function BuildCredentialsSaveQuery($obj, &$vars)
	{
		$className = get_class($obj);
		$fields = "";
		$values = "";
		$update = "";

		if ($className != "Member" && $className != "StakeLeader")
			return false;

		@ $recordID = $vars['ID'] ? $vars['ID'] : 0;
		@ $credID = $vars['CredentialsID'] ? $vars['CredentialsID'] : 0;
		@ $email = $vars['Email'];
		@ $password = $vars['Password'];
		@ $salt = $vars['Salt'];

		$credID = DB::Safe($credID);
		$recordID = DB::Safe($recordID);
		$email = DB::Safe($email);
		$password = DB::Safe($password);
		$salt = DB::Safe($salt);

		unset($vars['Email']);			// Unset these here because they don't go in the Member/StakeLeader tables!
		unset($vars['Password']);
		unset($vars['Salt']);
		
		return "INSERT INTO Credentials (`ID`, `Email`, `Password`, `Salt`, `{$className}ID`) VALUES ('{$credID}', '$email', '$password', '$salt', '$recordID') ON DUPLICATE KEY UPDATE Email='$email', Password='$password', Salt='$salt', `{$className}ID`='$recordID'";
	}

	// VERY important security measure for all values being inserted into the database.
	public static function Safe($value, $stripTags = true)
	{
		return $stripTags ? mysql_real_escape_string(strip_tags($value)) : mysql_real_escape_string($value);
	}
}


/*

EXPLANATION ABOUT THE SQL MODE LINE ABOVE:

SQL queries starting bombing seemingly randomly one day after I updated my dev machine... after
pulling my hair out for days trying to figure out why... I found the culprit...
	
MySQL 5.6.8 had an unexpected breaking change that this code was not originally designed to handle.
On the changelog page (http://dev.mysql.com/doc/relnotes/mysql/5.6/en/news-5-6-8.html),
we see that mysql_install_db has some different behavior:

----------

"On Unix platforms, mysql_install_db now creates a default option file named my.cnf in the base installation directory.
This file is created from a template included in the distribution package named my-default.cnf. You can find
the template in or under the base installation directory. When started using mysqld_safe, the server uses
my.cnf file by default. If my.cnf already exists, mysql_install_db assumes it to be in use and writes a new
file named my-new.cnf instead.

"With one exception, the settings in the default option file are commented and have no effect. The exception is
that the file changes the sql_mode system variable from its default of NO_ENGINE_SUBSTITUTION to also include
STRICT_TRANS_TABLES. This setting produces a server configuration that results in errors rather than warnings
for bad data in operations that modify transactional tables. See Server SQL Modes."

----------

(More info: https://blogs.oracle.com/supportingmysql/entry/mysql_server_5_6_default)

Before, inserting an empty string into a numeric field would simply cause the table engine
to default to the field's default or empty or NULL value. This is very convenient! However,
since this change, the very same input yields a nasty error which causes some serious doo-doo
in PHP scripts and Javascript handlers.

The query which sets the session's sql_mode to empty value removes the STRICT_TRANS_TABLES option
which restores the previous, desired behavior. (I don't think this site needs the NO_ENGINE_SUBSTITUTION
option.)

Here are some queries related to this topic I used in my discovery of this bug:

SET @SQL_MODE='STRICT_TRANS_TABLES';
SET GLOBAL SQL_MODE='STRICT_TRANS_TABLES';
SELECT @@GLOBAL.sql_mode;
SELECT @@SESSION.sql_mode;
SET SESSION sql_mode='STRICT_TRANS_TABLES';		# Case insensitive, I believe
SET SESSION sql_mode=''

*/

?>