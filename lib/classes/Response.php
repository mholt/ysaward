<?php

class Response
{
	/**
	Getting WEIRD "500" errors on production. Took me by surprise.
	Originally didn't need the array... (on my dev machine)...
	Before, I could just do this:
	header('x', true, $status);

	And 'x' got replaced with the right status message. But not
	on production, for some reason. Got this weird error:
	[Mon May 28 14:37:43 2012] [error] [client 69.169.187.164] malformed header from script. Bad header=x: login.php

	The network console showed a response from the server like this:

	<!-- SHTML Wrapper - 500 Server Error --> [an error occurred while processing this directive]

	I still don't know why, but this temporary/hackish change I made below
	allows it to run on production.

	UPDATE: By passing a " " into the status text parameter of header(), it seems to work...

	**/

	public $Statuses = array(
		200 => "OK",
		400 => "Bad Request or Input",
		401 => "Unauthorized",
		402 => "Payment Required",
		403 => "Forbidden",
		404 => "Not Found",
		405 => "Method Not Allowed",
		406 => "Not Acceptable",
		407 => "Proxy Authentication Required",
		408 => "Request Timeout",
		409 => "Conflict",
		410 => "Gone",
		500 => "Internal Server Error",
		501 => "Not Implemented",
		502 => "Bad Gateway",
		503 => "Service Unavailable",
		504 => "Gateway Timeout",
		505 => "HTTP Version Not Supported"
	);

	public static function Send($status, $msg = "")
	{
		if (!is_numeric($status))
			fail("Status code must be a number.");

		header(' ', true, $status);
		//header("HTTP/1.1 {$status} ".Response::Statuses[$status]);
		if ($msg)
			echo $msg;
		exit;
	}
}
?>