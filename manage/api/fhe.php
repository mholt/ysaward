<?php
require_once("../../lib/init.php");
protectPage(7);


// Find out what we're expecting to do here:
// create, delete, assign a leader, or edit the group.
$action = "";
if (isset($_GET['new']))
	$action = "new";
elseif (isset($_GET['del']))
	$action = "del";
elseif (isset($_GET['assign']))
	$action = "assign";
elseif (isset($_GET['edit']))
	$action = "edit";
else
	Response::Send(400, "Not sure what to do.");


if ($action == "new")
{
	@ $name = $_POST['groupname'];
	@ $ldr1 = $_POST['ldr1'];
	@ $ldr2 = $_POST['ldr2'];
	@ $ldr3 = $_POST['ldr3'];

	if (!$name)
		Response::Send(400, "Can't create a group unless there's a name for it.");

	if (
		($ldr1 == $ldr2 && $ldr1 != 0) ||
		($ldr2 == $ldr3 && $ldr2 != 0) ||
		($ldr1 == $ldr3 && $ldr3 != 0)
	   )
	{
		Response::Send(400, "Each leader of the group must be different.");
	}

	$fhe = new FheGroup();
	$fhe->GroupName = $name;
	$fhe->Leader1 = $ldr1;
	$fhe->Leader2 = $ldr2;
	$fhe->Leader3 = $ldr3;
	$fhe->WardID = $MEMBER->WardID;

	// This next for loop is the SAME THING as in the "Edit" part later on...
	// Make sure new leaders are removed from any existing group leaderships.
	// TODO: This way of doing it is too redundant. I want to redo this another time. What if the
	// leadership becomes discombobulated? (e.g. removes a leader1 but keeps leader 2... just looks weird)
	// This is a messy implementation. That's what I get for being in a hurry, I guess.
	for ($i = 1; $i <= 3; $i ++)
		DB::Run("UPDATE FheGroups SET Leader{$i}=0 WHERE Leader{$i}='$ldr1' OR Leader{$i}='$ldr2' OR Leader{$i}='$ldr3'");



	if ($fhe->Save())
	{
		// Put leaders into the group
		if ($ldr1 > 0)
		{
			$ldr = Member::Load($ldr1);
			$ldr->FheGroup = $fhe->ID();
			$ldr->Save();
		}

		if ($ldr2 > 0)
		{
			$ldr = Member::Load($ldr2);
			$ldr->FheGroup = $fhe->ID();
			$ldr->Save();
		}

		if ($ldr3 > 0)
		{
			$ldr = Member::Load($ldr3);
			$ldr->FheGroup = $fhe->ID();
			$ldr->Save();
		}

		$fhe->ConsolidateLeaders();

		Response::Send(200);
	}
	else
		Response::Send(500, "There was a problem; could not save FHE group.");
}

elseif ($action == "assign")
{
	$memID = $_POST['user'];
	$groupID = $_POST['group'];

	$mem = Member::Load($memID);
	if (!$mem)
		Response::Send(500, "Bad member ID");

	$group = FheGroup::Load($groupID);
	$currentGroup = $mem->FheGroup();

	$removedLeader = false;
	if ($currentGroup)
	{
		// Remove this member from group leadership if necessary
		if ($currentGroup->Leader1 == $mem->ID())
		{
			$currentGroup->Leader1 = 0;
			$removedLeader = true;
		}
		elseif ($currentGroup->Leader2 == $mem->ID())
		{
			$currentGroup->Leader2 = 0;
			$removedLeader = true;
		}
		elseif ($currentGroup->Leader3 == $mem->ID())
		{
			$currentGroup->Leader3 = 0;
			$removedLeader = true;
		}

		// Consolidate leadership (e.g. leader1, leader3, but no leader2 = messy)
		// This also persists (saves) the object in the DB.
		$currentGroup->ConsolidateLeaders();
	}

	// Assign to new group
	$mem->FheGroup = $groupID;

	// Build the response
	$response = "Success";
	if (!$groupID)
		$response = "Removed {$mem->FirstName} from ".Gender::PossessivePronoun($mem->Gender)." group.";
	else
		$response = "Assigned {$mem->FirstName} to group {$mem->FheGroup()->GroupName}.";

	if ($removedLeader)
		$response .= " This member is no longer a leader of ".Gender::PossessivePronoun($mem->Gender)." old group.";

	if ($mem->Save())
		Response::Send(200, $response);
	else
		Response::Send(500, "Something went wrong; could not save member's new assignment.");
}

elseif ($action == "del")
{
	$id = DB::Safe($_GET['id']);

	// Remove all members from this group which is about to be deleted
	$r = DB::Run("UPDATE Members SET FheGroup=0 WHERE FheGroup=$id");

	if (!$r)
		Response::Send(500, "Could not remove members from FHE group: ".mysql_error());

	// Delete the group
	$r = DB::Run("DELETE FROM FheGroups WHERE id=$id LIMIT 1");

	if (!$r)
		Response::Send(500, "Members removed from FHE group, but could not delete FHE group because: ".mysql_error());
	else
		Response::Send(200);
}

elseif ($action == "edit")
{
	$id = $_POST['id'];

	$group = FheGroup::Load($id);
	if (!$group)
		Response::Send(500, "Bad group ID");

	@ $name = $_POST['groupname'];
	@ $ldr1 = $_POST['ldr1'];
	@ $ldr2 = $_POST['ldr2'];
	@ $ldr3 = $_POST['ldr3'];

	if (!$name)
		Response::Send(400, "Please type a group name.");

	
	// Make sure new leaders are removed from old group leaderships.
	// This next for loop is the exact same as the loop above near the top of this file.
	// TODO: This setup is awful. I want to redo this another time. What if the
	// leadership becomes discombobulated? (e.g. removes a leader1 but keeps leader 2... just looks weird)
	// This is a messy implementation. That's what I get for being in a hurry, I guess.
	//DB::Run("UPDATE FheGroups SET Leader1=0 WHERE Leader1='$ldr1' OR Leader1='$ldr2' OR Leader1='$ldr3'");
	//DB::Run("UPDATE FheGroups SET Leader2=0 WHERE Leader2='$ldr1' OR Leader2='$ldr2' OR Leader2='$ldr3'");
	//DB::Run("UPDATE FheGroups SET Leader3=0 WHERE Leader3='$ldr1' OR Leader3='$ldr2' OR Leader3='$ldr3'");
	for ($i = 1; $i <= 3; $i ++)
		DB::Run("UPDATE FheGroups SET Leader{$i}=0 WHERE Leader{$i}='$ldr1' OR Leader{$i}='$ldr2' OR Leader{$i}='$ldr3'");

	// Make assignments, but don't save changes yet.
	$group->GroupName = $_POST['groupname'];
	$group->Leader1 = $_POST['ldr1'];
	$group->Leader2 = $_POST['ldr2'];
	$group->Leader3 = $_POST['ldr3'];


	// Move the leaders into their new groups
	if ($group->Leader1 > 0)
	{
		$mem = Member::Load($group->Leader1);
		$mem->FheGroup = $id;
		$mem->Save();
	}
	if ($group->Leader2 > 0)
	{
		$mem = Member::Load($group->Leader2);
		$mem->FheGroup = $id;
		$mem->Save();
	}
	if ($group->Leader3 > 0)
	{
		$mem = Member::Load($group->Leader3);
		$mem->FheGroup = $id;
		$mem->Save();
	}


	if ($group->ConsolidateLeaders()) // Persists the object in the DB
		Response::Send(200);
	else
		Response::Send(500, "Something went wrong; could not save group...");
}
?>