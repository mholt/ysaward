<?php
require_once("../lib/init.php");

// Returns 200 OK if the submitted password
// is a correct ward/password combination.
// Also sets the ward_id session variable.
// A successful result should let the user
// proceed to registration.

@ $wardID = $_POST['ward_id'];
@ $pwd = trim($_POST['pwd']);

$ward = Ward::Load($wardID);

if (!$ward)
	Response::Send(404, "Please choose a ward. If you did, contact your ward website person.");

if (!$ward->PasswordMatches($pwd))
	Response::Send(401, "Wrong ward/password combination. Please try again!");

// If they get to this point, successful authentication. A-OK.
$_SESSION['ward_id'] = $ward->ID();
Response::Send(200);

?>
