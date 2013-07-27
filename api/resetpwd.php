<?php
require_once("../lib/init.php");

if ($MEMBER || $LEADER)
	Response::Send(403, "You're already logged in!");

@ $eml = $_POST['eml'];

if (!$eml)
	Response::Send(400, "Please type your email address.");

$eml = DB::Safe($eml);

// Verify input, send email, etc.
$q = "SELECT ID FROM Credentials WHERE Email='{$eml}' LIMIT 1";
$r = DB::Run($q);

if (mysql_num_rows($r) == 0)
	Response::Send(400, "That email address {$eml} doesn't match any on file.");

// Get credential ID
$credID = mysql_result($r, 0);

// Make sure they haven't requested a reset in the last 15 minutes.
$q = "SELECT `Timestamp` FROM `PwdResetTokens` WHERE `CredentialsID`='$credID' ORDER BY `ID` DESC LIMIT 1";		// Find most recent
$result = mysql_fetch_array(DB::Run($q));
$tooSoon = strtotime("+15 minutes", strtotime($result['Timestamp']));
if (time() < $tooSoon)
	Response::Send(403, "Please wait at least 15 minutes before requesting another email to be sent.");


// Generate reset token
$token = randomString(15, false);


// Prepare the email
$subj = "Reset your ward website password";
$msg = "Hi!

You or somebody else is trying to login to this account on ".SITE_DOMAIN.".

To reset your password, click on or navigate to this link:

----------------------------------------------------
https://".SITE_DOMAIN."/newpwd.php?key={$token}
----------------------------------------------------

If you didn't ask for a password reset, just ignore and delete this message. It expires in 48 hours anyway.

Have a great day!
-".SITE_DOMAIN;


// Save the reset token in the DB
$q = "INSERT INTO `PwdResetTokens` (CredentialsID, Token, Timestamp) VALUES ('{$credID}', '$token', CURRENT_TIMESTAMP)";
if (!DB::Run($q))
	Response::Send(500, "Couldn't save password reset token. Please report this: ".mysql_error());


// Send the email
$mail = new Mailer();
$mail->FromAndReplyTo(SITE_NAME, "no-reply@".SITE_DOMAIN);
$mail->Subject("Reset your ward website password");
$mail->Body($msg);
$mail->To("", $eml);
$mail->Send();

if (count($mail->FailedRecipients()) > 0)
	Response::Send(500, "Could not send password reset email. Please try again, or report this if the problem persists.");

// Send 200 OK. Email sent; we're done here.
Response::Send(200);
?>