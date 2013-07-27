<?php
require_once("../../lib/init.php");
protectPage(13);

@ $users = $_POST['users'];

if (!isset($users) || !count($users))
	Response::Send(400, "You must specify at least one account to delete.");

$mems = array();
foreach ($users as $id)
{
	$mem = Member::Load($id);

	if (!$mem)
		fail("ERROR > User with ID $id couldn't be loaded. Are you sure the account exists? Aborting.");
	if ($mem->ID() == $MEMBER->ID())
		fail("ERROR > You can't delete your own account");
	if ($mem->WardID != $MEMBER->WardID)
		fail("ERROR > You can only delete accounts of members in your own ward. User with ID {$mem->ID()} is not in your ward.");

	$mems[] = $mem;
}

foreach ($mems as $mem)
	if (!$mem->Delete(true))
		fail("Could not delete member with ID {$mem->ID()}... but all others before him/her were deleted.");

header("Location: ../prune.php?success=true");

?>