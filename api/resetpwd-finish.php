<?php
require_once("../lib/init.php");

@ $pwd1 = $_POST['pwd1'];
@ $pwd2 = $_POST['pwd2'];
@ $credID = $_POST['credID'];
@ $token = $_POST['token'];

if (!$pwd1 || !$pwd2)
	header("Location: /newpwd.php?key=$token");
if (!$token)
	Response::Send(400, "Token value disappeared. What happened, I wonder? Please report this so it can get fixed.");

// Make sure both passswords match
if ($pwd1 != $pwd2)
	Response::Send(400, "Your passwords don't match. Make sure they match.");

// Check length
if (strlen($pwd1) < 8)
	Response::Send(400, "Your password is too short. Please make it at least 8 characters.");

// Verify that the credentials ID matches the token
$credID = DB::Safe($credID);
$token = DB::Safe($token);
$q = "SELECT 1 FROM `PwdResetTokens` WHERE `CredentialsID`='$credID' AND `Token`='$token' LIMIT 1";
$r = DB::Run($q);
if (mysql_num_rows($r) == 0)
	Response::Send(400, "Account ID and token do not appear to match. Maybe try again from the link in your email?");

// Get account object (Member or Leader) -- first we have to determine which type it is
$q2 = DB::Run("SELECT * FROM Credentials WHERE ID='{$credID}' LIMIT 1");
$r = mysql_fetch_array($q2);
$memberID = $r['MemberID'];
$leaderID = $r['StakeLeaderID'];
$user = null;

if ($memberID && !$leaderID)
	$user = @Member::Load($memberID);
else if ($leaderID && !$memberID)
	$user = @StakeLeader::Load($leaderID);

if (!$user)
	Response::Send(500, "Could not load account with ID '$memberID' or '$leaderID', from credentials ID $credID -- please report this exact error message. Thanks...");

// Reset password.
if (!$user->ChangePassword($pwd1)) // This function deletes the token from the DB for us
	Response::Send(500, "Could not reset your password for some reason... please report this.");

// In the clear!
Response::Send(200);

?>