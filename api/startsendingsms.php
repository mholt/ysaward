<?php
require_once("../lib/init.php");
protectPage();

/*
	TODO: This file does not support stake leader sending.
	Before stake leaders should be able to send texts to an entire
	stake, make sure to add at least 2 or 3 numbers to your
	Nexmo account, so that the load can be round-robin'ed. Otherwise,
	you'll exceed throughput limits on a single number.
*/


// NOTE: Currently, anyone with the privilege to send to their FHE
// group can send more than the max per day (but not more than that all
// at once) -- this is because it's really hard to figure out who's
// in the FHE group and who's not... so I'm just going to let that slide
// for now and hope it's not abused. The logic is ugly.
$canSendAll = $MEMBER->HasPrivilege(PRIV_TEXT_ALL);
$canSendFHE = $MEMBER->HasPrivilege(PRIV_TEXT_FHE);


// The member doesn't need the *privilege* to send to FHE if they're a group leader
$group = $MEMBER->FheGroup();
if ($group->Leader1 == $MEMBER->ID()
		|| $group->Leader2 == $MEMBER->ID()
		|| $group->Leader3 == $MEMBER->ID())
	$canSendFHE = true;


// Send emails to selected recipients. First, check input.
@ $recipients = $_POST['to'];
@ $msg = trim($_POST['msg']);
@ $fhe = isset($_POST['fhe']);

if (!isset($recipients) || !count($recipients) || !$msg)
	Response::Send(400, "You must specify at least one recipient and a message.");


// Does this person already have a job in the queue? If so, 
// Member already has job in the queue?
if (SMSJob::UnfinishedJobExistsWithMemberID($MEMBER->ID()))
	Response::Send(403, "You already have a text in the process of being sent. Please wait until it is finished, so just try again in a minute or two.");

$recipCount = count($recipients);

// Make sure they aren't sending more than they're allowed to
$textsRemaining = SMS_MAX_PER_DAY - $MEMBER->TextMessagesSentInLastDay();
if (($recipCount * ceil(strlen($msg) / SMS_CHARS_PER_TEXT) > $textsRemaining) && !$fhe)
{
	if (!$canSendAll && !$canSendFHE)
		Response::Send(403, "You may only send up to ".SMS_MAX_PER_DAY." texts in a 24-hour period.");
}

// Build array of recipient Member objects
$recipientMembers = array();
$failedRecipientMembers = array();
foreach ($recipients as $memberid)
{
	$mem = Member::Load($memberid);

	if (!$mem)
		continue;

	if (!$mem->ReceiveTexts)
	{
		$failedRecipientMembers[] = $mem;
		continue;
	}

	// Format their phone number just to be sure, but do NOT save the Member object.
	// US numbers must start with a "1" ("+1" is the US country code, making 11 digits total)
	// -- also strip non-numeric characters just in case

	if (strlen($mem->PhoneNumber) == 10)
		$mem->PhoneNumber = "1".preg_replace("/[^0-9]+/", "", $mem->PhoneNumber);

	$recipientMembers[] = $mem;
}


// Now create the job and send it off!
$job = new SMSJob();

$job->WardID = $WARD->ID();

$job->SenderID = $MEMBER->ID();

foreach ($recipientMembers as $mem)
	$job->AddRecipient($mem->ID(), $mem->FirstName()." ".$mem->LastName, $mem->PhoneNumber);

foreach ($failedRecipientMembers as $mem)
	$job->AddFailedRecipient($mem->ID(), $mem->FirstName()." ".$mem->LastName, $mem->PhoneNumber, "Member opted out of receiving text messages");

$job->Message = $msg;

if (!$job->Start())
	Response::Send(500, "Couldn't send text messages; something went wrong. Please contact your ward website person to fix this.");

Response::Send(200);
?>