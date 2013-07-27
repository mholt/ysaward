<?php
require_once("../../lib/init.php");
protectPage(11);

@ $id = DB::Safe($_GET['id']);

$c = Calling::Load($id);

if (!is_object($c))
	fail("Bad calling ID.");

if ($c->Preset())
	fail("Can't delete a pre-defined calling.");

if ($c->WardID() != $MEMBER->WardID)
	Response::Send(403, "Can't delete a calling which does not belong to your ward.");

if ($c->Delete(true))
	Response::Send(200);
else
	fail("Oops, something went wrong. Please report this error.");

?>