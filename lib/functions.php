<?php

function now()
{
	// Returns a current timestamp for MySQL "Timestamp"
	// In case we can't use the NOW() function in the query
	return date("Y-m-d H:i:s");
}

// Thanks to http://davidwalsh.name/php-email-encode-prevent-spam for the one-liner
function encode_email($e) {
	$output = "";
	for ($i = 0; $i < strlen($e); $i++) { $output .= "&#".ord($e[$i]).";"; }
	return $output;
}

function sqldate($date)
{
	// Returns a SQL-valid date format given a string.
	// If it fails, returns the original input. This
	// won't work well for MySQL timestamp fields,
	// but for survey answers this at least preserves
	// the user's input.
	$timestamp = @strtotime($date);
	return !$timestamp ? $date : date("Y-m-d H:i:s", $timestamp);
}

// Computes the difference in days between current date and another date
function dayDifference($date)
{
	return dateInterval($date)->days;
}

// Computes an interval of time between now and some datetime
function dateInterval($date)
{
	$date1 = new DateTime();
	$date2 = new DateTime($date);
	return $date1->diff($date2);
}


// Sleeps for a given number of milliseconds.
function millisleep($milliseconds)
{
	usleep($milliseconds * 1000);
}

// Alias for Response::Send, but with status code 500.
// This allows us to kill a script in an AJAX-friendly way.
function fail($msg)
{
	Response::Send(500, $msg);
}

function euroToUsdRate()
{
	$result = json_decode(file_get_contents("http://rate-exchange.appspot.com/currency?from=EUR&to=USD"));
	return $result->rate;
}


// Returns a new random salt, length 16, for a
// user's password, of only alphanumeric characters
function salt()
{
	return randomString(16, false);
}

// Takes a plaintext password and a random, unique salt and returns
// the hashed equivalent using our site's hashing algorithm.
// The salt is technically optional, but strongly recommended
// for user accounts (and actually required for them to login).
function hashPwd($plaintext, $salt = "")
{
	// We would use Blowfish (crypt function) because it's much slower (a good thing)
	// however it's only supported on PHP 5.3+. For portability reasons, I've opted
	// not to implement it by default. (So, TODO: Use bcrypt!)

	// In the first days of the site, there was a static salt and different
	// algorithm we chose to use. In order to be backward-compatible/non-breaking, we
	// had to create a newer, more secure hash with dynamic salt
	// on top of it. This way users didn't all have to reset their passwords.

	// First, we must use the old hash algorithm and re-hash on top of it
	// with a salt, which should be unique and random for each user.
	$oldHashAlgorithm = hash('sha512', hash('sha256', $plaintext)."d!P*3Dlw");
	$finalHash = hash('whirlpool', $oldHashAlgorithm.$salt);

	return $finalHash;
}


// Prevent users who are not logged in from accessing the page
// By default, allows regular members through. To restrict access
// to only certain privileges, be sure to pass in a value > 0.
// By default, doesn't allow stake leaders. Explicitly set
// second argument to true to allow them (pass in 0 for privilege ID).
// This function is very important...
function protectPage($privilegeID = 0, $allowStakeLeader = false)
{
	$isMember = Member::IsLoggedIn();
	$isLeader = StakeLeader::IsLoggedIn();

	if ((!$isMember && !$isLeader)
		|| ($privilegeID && !$isMember)	// This condition shouldn't ever happen, right?
		|| ($isLeader && !$allowStakeLeader))
	{
		header("Location: /");
		exit; 	// not usually necessary, but extra safety
	}

	if ($privilegeID && $isMember)
	{
		$current = Member::Current();	// (The $MEMBER variable hasn't been created yet)
		if (!$current->HasPrivilege($privilegeID))
		{
			header("Location: /directory.php");
			exit;
		}
	}
	
	// Require new members to fill out the survey (this works until they are logged out for the first time)
	if (isset($_SESSION['isNew']) && strpos($_SERVER['REQUEST_URI'], "answers.php") === false)
		header("Location: /answers.php");
}


// Returns the client's newline character(s)
function clientCRLF()
{
    $ua = $_SERVER['HTTP_USER_AGENT'];

    if (stripos($ua, "Windows") !== false
    	|| stripos($ua, "OS X") !== false)
    	return "\r\n";	// Windows, Mac OS X+
    elseif (stripos($ua, "Macintosh") !== false)
    	return "\r";	// Mac < OS X
    else
    	return "\n";	// Linux, FreeBSD, etc.
}

// Formats a string's newlines for the DB
// Even though it's a Unix machine, we're using the
// Windows linebreak in the DB because it is the most
// explicit; \r and \n are both contained in \r\n.
// This function may not preserve multiple line breaks;
// it is intended for newline-delimited strings.
function formatForDB($string)
{
	// I'd like to use preg_replace but can't quite get a good
	// regex for it.
	$string = str_replace("\r", "\n", $string);
	$string = str_replace("\n\n", "\n", $string);
	$string = str_replace("\n", "\r\n", $string);
	return trim(str_replace("\r\n\r\n", "\r\n", $string));
}

// Formats a string's newlines based on the client OS (see above)
function formatForClient($string)
{
	$string = str_replace("\r\n", "\n", $string);
	$string = str_replace("\r", "\n", $string);
	$string = str_replace("\n\n", "\n", $string); // May be extraneous, but just in case..
	return str_replace("\n", clientCRLF(), $string);
}

// Given a phone number string containing only numbers,
// formats it for display
// E.g. turns "1234567890" into "123-456-7890"
function formatPhoneForDisplay($phoneString)
{
	if (!$phoneString || strlen($phoneString) < 4)
		return '';

	if (strlen($phoneString) == 11)
	{
		$phoneString = substr_replace($phoneString, '-', 1, 0);
		$phoneString = substr_replace($phoneString, '-', 5, 0);
		return substr_replace($phoneString, '-', 9, 0);
	}
	else if (strlen($phoneString) <= 7)
		return substr_replace($phoneString, '-', 3, 0);
	else
	{
		$phoneString = substr_replace($phoneString, '-', 3, 0);
		return substr_replace($phoneString, '-', 7, 0);
	}
}

// Strips the extension and returns only the base filename,
// given a string that contains a filename or path.
function filename($str)
{
	$info = pathinfo($str);
	return basename($str, '.'.$info['extension']);
}

// Returns the extension of a file name/path string (without the . character)
function extension($str)
{
	$info = pathinfo($str);
	return $info['extension'];
}


/**
 * Generates a random string of ASCII characters with the given length.
 * @param $length The length of the string to generate.
 * @param $specialChars	If true, include special characters (default).
 * @author Matthew Holt 2012
 * @return The random string of characters.
 */
function randomString($length, $specialChars = true)
{
	$asciiMin = 33;
	$asciiMax = 126;
	$alphanum = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
	$alphanumLen = strlen($alphanum);
	$str = "";
	if ($specialChars)
		for ($i = 0; $i < $length; $i++)
			$str .= chr(mt_rand($asciiMin, $asciiMax));
	else
		for ($i = 0; $i < $length; $i++)
			$str .= $alphanum[mt_rand(0, $alphanumLen - 1)];
	return $str;
}




/* From PHP.net manual, a note added by a user, in entry "imagecopyresampled" */
// MODIFIED SLIGHTLY (see $off_h ...)
function create_jpgthumb($original, $thumbnail, $max_width, $max_height, $quality, $scale = true)
{
	ini_set("gd.jpeg_ignore_warning", 1);	// For intermittent problems with imagecreatefromjpeg() below; see: http://stackoverflow.com/q/3901455/1048862
	ini_set('memory_limit', '64M');
	list ($src_width, $src_height, $type, $w) = getimagesize($original);
	
	if (!($srcImage = imagecreatefromjpeg($original)))
		return false;

	if ($scale == true)
	{
		// image resizes to natural height and width

		if ($src_width > $src_height)
		{
			$thumb_width = $max_width;
			$thumb_height = floor($src_height * ($max_width / $src_width));
		}
		else if ($src_width < $src_height)
		{
			$thumb_height = $max_height;
			$thumb_width = floor($src_width * ($max_height / $src_height));
		}
		else
		{
			$thumb_width = $max_height;
			$thumb_height = $max_height;
		}

		if (!$destImage = imagecreatetruecolor($thumb_width, $thumb_height))
			return false;

		if (!imagecopyresampled($destImage, $srcImage, 0, 0, 0, 0, $thumb_width, $thumb_height, $src_width, $src_height))
			return false;
	}
	else if ($scale == false)
	{
		// image is fixed to supplied width and height and cropped

		$ratio = $max_width / $max_height;

		// thumbnail is landscape
		if ($ratio > 1)
		{
			// uploaded pic is landscape
			if ($src_width > $src_height)
			{
				$thumb_width = $max_width;
				$thumb_height = ceil($max_width * ($src_height / $src_width));

				if ($thumb_height > $max_width)
				{
					$thumb_height = $max_width;
					$thumb_width = ceil($max_width * ($src_width / $src_height));
				}
			}
			else
			{
				// uploaded pic is portrait
				$thumb_height = $max_width;
				$thumb_width = ceil($max_width * ($src_height / $src_width));

				if ($thumb_width > $max_width) {
					$thumb_width = $max_width;
					$thumb_height = ceil($max_width * ($src_height / $src_width));
				}

				$off_h = ($src_height - $src_width) / 4;	// Divide by 2 to get segment in middle (but heads are usually near the top)
			}

			if (!$destImage = imagecreatetruecolor($max_width, $max_height))
				return false;

			if (!imagecopyresampled($destImage, $srcImage, 0, 0, 0, $off_h, $thumb_width, $thumb_height, $src_width, $src_height))
				return false;
		 }
		 else
		 {
		 	// thumbnail is square
			if ($src_width > $src_height)
			{
				$off_w = ($src_width - $src_height) / 2;
				$off_h = 0;
				$src_width = $src_height;
			}
			else if ($src_height > $src_width)
			{
				$off_w = 0;
				$off_h = ($src_height - $src_width) / 4;	// Divide by 2 to get segment in middle (but heads are usually near the top)
				$src_height = $src_width;
			}
			else
			{
				$off_w = 0;
				$off_h = 0;
			}

			if (!$destImage = imagecreatetruecolor($max_width, $max_height))
				return false;

			if (!imagecopyresampled($destImage, $srcImage, 0, 0, $off_w, $off_h, $max_width, $max_height, $src_width, $src_height))
				return false;
		 }
	}

	imagedestroy($srcImage);

	if (function_exists('imageantialias'))
	{
		// Requires the bundled version of the GD library (Ubuntu has a beef with this)
		if (!imageantialias($destImage, true))
			return false;
	}

	if (!imagejpeg($destImage, $thumbnail, $quality))
		return false;

	imagedestroy($destImage);
	return true;
}

// Converts letters in a phone number string to numbers
function phoneAlphaToNumeric($string)
{
	$newstring = '';

	foreach(str_split($string) as $char)
	{
		switch (strtolower($char))
		{
			case 'a';
			case 'b';
			case 'c';
				$num = 2;
				break;
			case 'd';
			case 'e';
			case 'f';
				$num = 3;
				break;
			case 'g';
			case 'h';
			case 'i';
				$num = 4;
				break;
			case 'j';
			case 'k';
			case 'l';
				$num = 5;
				break;
			case 'm';
			case 'n';
			case 'o';
				$num = 6;
				break;
			case 'p';
			case 'q';
			case 'r';
			case 's';
				$num = 7;
				break;
			case 't';
			case 'u';
			case 'v';
				$num = 8;
				break;
			case 'w';
			case 'x';
			case 'y';
			case 'z';
				$num = 9;
				break;
			default:
			 	$num = $char;
		}
		$newstring .= $num;
	}

	return $newstring;
}

// When we were starting out and still on BlueHost, they ran an old
// version of PHP. This function was helpful in avoiding using new
// features that weren't yet supported on the local environment, but is
// not used anymore. Maybe this should become a gist on GitHub.
function phpVersionInt()
{
	$version = explode('.', PHP_VERSION);

	// Must be two digits long, so fill with zeroes. Very important.
	if (strlen($version[0]) < 2)		// Major version
		$version[0] .= '0';

	$version[1] = isset($version[1]) ? $version[1] : '0';
	if (strlen($version[1]) < 2)		// Minor version
		$version[1] .= '0';

	$version[2] = isset($version[2]) ? $version[2] : '0';
	if (strlen($version[2]) < 2)		// Release version
		$version[2] .= '0';

	return implode('', $version);
}


// Vital function which loads classes as needed, according to naming conventions
// (ie. a class called "EmailJob" must be in "EmailJob.php" within the include path)
function autoload($className)
{
	require_once $className . ".php";
}


// Custom error handler callback, as used by error_handler() in init.php.  
function errorHandler($level, $errorMsg, $file, $line)
{
	// Don't handle it if the error was suppressed (maybe with @) (or error reporting is off)
	if (!error_reporting())
		return true;

	$errorType;
	if ($level == E_USER_ERROR)
		$errorType = "Error";
	else if ($level == E_USER_WARNING)
		$errorType = "Warning";
	else if ($level == E_USER_NOTICE)
		$errorType = "Notice";
	else
		$errorType = "Unknown";

	$alertSubject = $errorType.": ".$errorMsg;
	$alertBody = "FILE: {$file}\r\nLINE: {$line}\r\nPROBLEM: {$errorType}: {$errorMsg}\r\n";

	// See if there's a logged-in user
	$user = Member::Current();
	if (!$user)
		$user = StakeLeader::Current();

	if ($user)
		$alertBody .= "Currently logged-in user:\r\n".print_r($user, true);

	$alertBody .= "\r\n\r\n--\r\nAutomatically generated by the PHP error handling subsystem for debugging purposes.";

	$mail = new Mailer();
	$mail->FromAndReplyTo(ERR_HANDLE_FROM_NAME, EMAIL_BLACKHOLE);
	$mail->Subject($alertSubject);
	$mail->Body($alertBody);
	$mail->To(WEBMASTER_NAME, WEBMASTER_EMAIL);
	$mail->Send();


	// Write this to the server's internal log file...
	error_log("{$errorType}: {$errorMsg} in {$file} on line {$line}\n", LOG_TYPE, LOG_FILE);

	// Don't execute PHP's internal error handler
	return true;
}

?>