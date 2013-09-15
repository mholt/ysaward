<?php
/*
	This file helps insert a new stake leader account into the system.
	Make sure only the webmaster can access it.
*/

exit;	// SAFETY LINE; comment-out or remove this line to use this file.



require_once("../lib/init.php");

$leader = new StakeLeader();
$leader->Email = "";				// Stake leader's email address
$leader->SetPassword("");			// Stake leader's account password
$leader->StakeID = 0;				// ID number of the stake he belongs to (Stakes table, column `ID`)
$leader->Gender = Gender::Male;		// Usually this is Male...
$leader->Calling = "";				// Calling of the stake leader, for example: "Stake Presidency Second Counselor" or "Stake President" or "Stake Executive Secretary"
$leader->Title = "";				// Title of the stake leader, for example: "President" or "Brother"
$leader->FirstName = "";			// First name of the stake leader (required)
$leader->MiddleName = "";			// Middle name or initial of the stake leader; not required
$leader->LastName = "";				// Last name of the stake leader
$leader->Save();					// Saves the leader.


echo "<pre>";
print_r($leader);					// Resulting StakeLeader object.
echo "</pre>";
exit;

?>