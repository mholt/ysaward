<?php
require_once("../../lib/init.php");
protectPage(9);

@ $id = DB::Safe($_GET['id']);

// Verify...
if (!$id)
	fail("Could not delete permission; please specify a valid ID.");

// Load the permission and make sure it belongs in the ward
$p = Permission::Load($id);
if (!$p->InWard($MEMBER->WardID))
	fail("That permission is not within your own ward...");

// Delete this permission.
$p->Delete(true);

Response::Send(200);

?>