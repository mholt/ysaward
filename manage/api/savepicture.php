<?php
require_once("../../lib/init.php");
protectPage(12);

// Grab the variables from the form
@ $pic = isset($_FILES['pic']) ? $_FILES['pic'] : array();
@ $memberID = $_POST['memberID'];

if (!$pic || count($pic) == 0)
	fail("Please choose a picture to upload.");

if (!$memberID)
	fail("No member was specified - please report this bug.");

$mem = Member::Load($memberID);

if (!$mem)
	fail("Could not load member with ID ".$memberID." - please report this bug.");

if ($mem->WardID != $MEMBER->WardID)
	fail("Member ".$memberID." is not in your ward.");

$mem->PictureFile(false, $pic);

// All done here
Response::Send(200, $memberID);
?>