<?php
require_once("../lib/init.php");

// Returns 200 OK if the submitted password
// is a correct ward/password combination.
// A successful result should let the user
// proceed to registration.

@ $wardID = $_POST['ward_id'];
@ $pwd = trim($_POST['pwd']);

$ward = Ward::Load($wardID);

if (!$ward)
	Response::Send(404, "Bad Ward ID -- please choose a ward, or contact your ward website person if there's a problem.");

if (!$ward->PasswordMatches($pwd))
	Response::Send(401, "Wrong ward/password combination. Please try again.");


// If they get to this point, successful authentication. A-OK.
$_SESSION['wardie'] = true;
$_SESSION['ward_id'] = $ward->ID();
Response::Send(200);

?>
