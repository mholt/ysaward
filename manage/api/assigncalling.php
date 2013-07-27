<?php
require_once("../../lib/init.php");
protectPage(11);

@ $cID = $_GET['cID'];
@ $mID = $_GET['mID'];

if (!$cID || !$mID)
	fail("No valid data passed here. Please report this...");

$mem = Member::Load($mID);
if ($mem == null)
	fail("Member with ID ".$mID." doesn't exist");

if ($mem->WardID != $MEMBER->WardID)
	Response::Send(403, "Can't assign callings to members not in your ward.");

if ($mem->AddCalling($cID))
	Response::Send(200);
else
	fail("Something went wrong; Check ".$cID." for member with ID ".$mID."... are you sure the calling or member wasn't just deleted, and that the calling exists in this ward?");
?>