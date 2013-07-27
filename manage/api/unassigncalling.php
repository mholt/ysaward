<?php
require_once("../../lib/init.php");
protectPage(11);

@ $mID = DB::Safe($_GET['mID']);
@ $cID = DB::Safe($_GET['cID']);

if (!$mID || !$cID)
	fail("Problem... missing member ID or calling ID or both.");

$m = Member::Load($mID);
if (!$m)
	fail("Could not load member with ID $mID. aborting.");

if (!$m->RemoveCalling($cID))
	fail("Could not remove calling from member.");

Response::Send(200);
?>