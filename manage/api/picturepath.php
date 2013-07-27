<?php
require_once("../../lib/init.php");
protectPage(12);

@ $memID = $_GET['member'];
@ $thumb = $_GET['thumbnail'];

if (!$memID)
	fail("No member specified");

$m = Member::Load($memID);

if (!$m)
	fail("Could not load member with ID ".$memID);

if ($m->WardID != $MEMBER->WardID)
	fail("Member is not in your ward");

Response::Send(200, $m->PictureFile($thumb));
?>