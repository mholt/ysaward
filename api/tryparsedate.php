<?php
require_once("../lib/common.php");

@ $input = trim($_GET['input']);

if (!$input)
	Response::Send(200);	// assume current date

// See if the input can be parsed into an actual date
@ $timestamp = strtotime($input);

// If the user types something like "4july1991", the parser will still get it,
// but we don't want to give back an error like "please type the full date"
// so insert spaces between "4july" and "july1991" to help out
$input = preg_replace('/([a-zA-Z])([0-9])|([0-9])([a-zA-Z])/', '$1 $2', $input);

// Dates entered must be full dates with 3 parts; a month, day, and year
$split_input = preg_split('/[^a-zA-Z0-9]/', $input, NULL, PREG_SPLIT_NO_EMPTY);


if (!$timestamp)
	Response::Send(400, "Date format not recognized. Try spelling it out, like: July 9, 1990");
else if (count($split_input) != 3)
	Response::Send(400, "Please enter a full date with month, day, and year, like: April 14, 1991");
else
	Response::Send(200);
?>