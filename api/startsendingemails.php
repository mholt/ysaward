<?php
require_once("../lib/init.php");
protectPage();






// Send emails to selected recipients. First, check input.
@ $recipients = $_POST['to'];
@ $subject = trim($_POST['subject']);
@ $msg = trim($_POST['msg']);
@ $fhe = isset($_POST['fhe']);

if (!isset($recipients) || !count($recipients) || !$subject || !$msg)
	Response::Send(400, "You must specify at least one recipient, a subject, and a message.");


// Does this person already have a job in the queue? If so, 
// Member already has job in the queue?
if (($MEMBER != null && EmailJob::UnfinishedJobExistsWithMemberID($MEMBER->ID()))
	|| ($LEADER != null && EmailJob::UnfinishedJobExistsWithLeaderID($LEADER->ID())))
	Response::Send(403, "You already have an email in the process of being sent. Please wait until it is finished before sending another. Try again in a minute or two.");

$recipCount = count($recipients);

// Did they send to themselves too? Let's check while we load all the recipient Member objects.
$sentToSelf = false;
$recipientMembers = array();
foreach ($recipients as $memberid)
{
	if ($memberid == $MEMBER != null ? $MEMBER->ID() : ($LEADER != null ? $LEADER->ID() : null))
	{
		$sentToSelf = true;
		$recipCount --;			// Sending to yourself doesn't count against you
	}
	$recipientMembers[] = Member::Load($memberid);
}

// Make sure they aren't sending more than they're allowed to
// Leaders can send as much as they want.
if ($recipCount > EMAIL_MAX_RECIPIENTS && !$fhe && $LEADER == null)
{
	// Get member's privileges in these matters
	$has1 = $MEMBER->HasPrivilege(PRIV_EMAIL_ALL);
	$has2 = $MEMBER->HasPrivilege(PRIV_EMAIL_BRO);
	$has3 = $MEMBER->HasPrivilege(PRIV_EMAIL_SIS);

	$maleCount = 0;
	$femaleCount = 0;

	foreach ($recipientMembers as $recipmem)
	{
		if ($recipmem->ID() == $MEMBER->ID())
			continue;

		if ($recipmem->Gender == Gender::Male)
			$maleCount ++;
		else
			$femaleCount ++;
	}

	if (!$has1 && !$has2 && !$has3)
		Response::Send(403, "You may only email up to EMAIL_MAX_RECIPIENTS members at a time.");

	if (!$has1)
	{
		if ($recipCount - $maleCount > EMAIL_MAX_RECIPIENTS && $has2)
			Response::Send(403, "You may send emails to all the brethren, but only up to EMAIL_MAX_RECIPIENTS more at a time after that.");
		if ($recipCount - $femaleCount > EMAIL_MAX_RECIPIENTS && $has3)
			Response::Send(403, "You may send emails to all the sisters, but only up to EMAIL_MAX_RECIPIENTS more at a time after that.");
	}
}

// If they didn't send a copy to themselves, let's do that for them as a courtesy.
if (!$sentToSelf)
	$recipientMembers[] = Member::Load($MEMBER->ID());


// Append privacy notice to end of message
$msg .= "

-----
This message was sent from the ward website in confidence that its contents and sender's name/email address will remain private to those involved in this communication.
"; //notice this newline. (that's important)

// If sent to more than one person, make that known.
if ($recipCount > 1)
{
	$msg .= "This message was sent to more than one person. ";	// trailing space is important
}

$msg .= "You received this message because you have an account on ".SITE_DOMAIN."."; // last part of the message.


// Now create the job and configure it (add recipients, etc.)
$job = new EmailJob();
if ($MEMBER != null && $LEADER == null) {
	$job->MemberID = $MEMBER->ID();
} else if ($MEMBER == null && $LEADER != null) {
	$job->StakeLeaderID = $LEADER->ID();
}

foreach ($recipientMembers as $mem)
	$job->AddRecipient($mem->FirstName()." ".$mem->LastName, $mem->Email);
$job->IsHTML = false;		// For now, just send plaintext emails.
$job->Subject = $subject;
$job->Message = $msg;


// Start the sending!
$job->Start();


if ($sentToSelf)
	Response::Send(200);		// Normal finish
else
	Response::Send(200, "1");	// Normal finish, and the front-end will inform the user that a courtesy copy was sent to them
?>