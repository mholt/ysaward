<?php
require_once("../../lib/init.php");
protectPage(10);

$redirectAppend = "";

if (sizeof($_POST) > 0)	// Used only for the "grant" action...
{
	@ $action = $_POST['action'];
	@ $objType = $_POST['objType'];
	@ $memberID = $_POST['memberID'];
	@ $callingID = $_POST['callingID'];
	@ $privIDs = $_POST['priv'];

	if ((!$action && size($_GET) == 0)
		|| ($objType != "Member" && $objType != "Calling")
		|| ($objType == "Member" && !$memberID)
		|| ($objType == "Calling" && !$callingID)
		|| !$privIDs || count($privIDs) == 0
		|| ($action != "grant" && $action != "revoke")
	)
	{
		fail("Please select a privilege and a member (or calling) to grant it to, or choose one to revoke -- then try again.");
	}


	if ($action == "grant")
	{
		// Grant these privileges

		foreach ($privIDs as $privID)
		{
			$priv = Privilege::Load($privID);

			if (!$priv)
				fail("Not a valid privilege ID ($privID), or had trouble loading it.");

			if ($objType == "Member")
			{
				$mem = Member::Load($memberID);

				if ($mem->WardID != $MEMBER->WardID)
					fail("You can only assign privileges to members of your ward.");

				if ($mem->HasPrivilege($privID))
					continue;
				else
					$priv->GrantToMember($memberID);

				$redirectAppend = "?granted#to-member";
			}
			else
			{
				$call = Calling::Load($callingID);

				if ($call->WardID() != $MEMBER->WardID)
					fail("You can only assign privileges to callings in your ward.");

				if ($call->HasPrivilege($privID))
					continue;
				else
					$priv->GrantToCalling($callingID);

				$redirectAppend = "?granted#to-calling";
			}
		}
	}
}
elseif (sizeof($_GET) > 0) // $_GET is used only for the "revoke" action...
{
	@ $action = $_GET['action'];
	@ $privID = $_GET['id'];
	@ $m = $_GET['m'];
	@ $c = $_GET['c'];

	if ($action == "revoke")
	{
		// Revoke this privilege

		if (!$privID)
			fail("Need a privilege ID to revoke; cannot revoke no privilege!");


		if (($m && $c) || (!$m && !$c))
			fail("Please choose a member or a calling to revoke from.");

		$priv = Privilege::Load($privID);

		if ($privID == 10)
		{
			// 10 is Manage Site Privileges; at least one member or calling from the ward should always have this.
			// This query gets a list of unique privileges.
			$epicQuery = "SELECT GrantedPrivileges.ID, Members.WardID FROM GrantedPrivileges
						INNER JOIN Members ON Members.ID = GrantedPrivileges.MemberID
						WHERE WardID = {$MEMBER->WardID} AND GrantedPrivileges.PrivilegeID = 10
						UNION
						SELECT GrantedPrivileges.ID, Callings.WardID FROM GrantedPrivileges
						INNER JOIN Callings ON Callings.ID = GrantedPrivileges.CallingID
						WHERE WardID = {$MEMBER->WardID} AND GrantedPrivileges.PrivilegeID = 10;";

			if (mysql_num_rows(DB::Run($epicQuery)) == 1)
				fail("At least one member or calling of your ward must be able to manage the site privileges. This was the last one; could not revoke.");
		}


		if ($m)
		{
			$mem = Member::Load($m);
			if ($mem->WardID != $MEMBER->WardID)
				fail("You can only revoke privileges from members of your ward.");
			else
				$priv->RevokeFromMember($m);

			$redirectAppend = "?revoked#by-member";
		}
		else
		{
			$call = Calling::Load($c);
			if ($call->WardID() != $MEMBER->WardID)
				fail("You can only revoke privileges of callings in your ward.");
			else
				$priv->RevokeFromCalling($c);

			$redirectAppend = "?revoked#by-calling";
		}
	}
	else
		fail("Bad request");
}
else
	fail("Bad request; no input found");


header("Location: ../privileges.php".$redirectAppend);

?>