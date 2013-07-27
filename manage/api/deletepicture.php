<?php
require_once("../../lib/init.php");
protectPage(12);

// Grab the variables from the form
@ $memberID = $_GET['member'];

if (!$memberID)
	fail("No member was specified; nothing to do.");

$mem = Member::Load($memberID);

if (!$mem)
	fail("Could not load member with ID ".$memberID." - please report this.");

if ($mem->WardID != $MEMBER->WardID)
	fail("Member ".$memberID." is not in your ward.");

if ($mem->DeletePictureFile())
	Response::Send(200, $memberID);
else
	fail("Could not delete profile picture, probably because the picture is already the default one.");

?>