<?php
require_once("../lib/init.php");
protectPage(0, true);


// This file allows a stake leader (ONLY) to change current wards.


@ $id = trim($_GET['id']);

if (!$LEADER || !$id)
{
	// User is not a stake leader or no ward ID was specified
	header("Location: /");
	exit;
}

$ward = Ward::Load($id);

if (!$ward || $ward->StakeID() != $LEADER->StakeID)
{
	// Bad ward ID or ward is not in this leader's stake
	header("Location: /");
	exit;
}

if ($ward->Deleted)
	fail("That ward is no longer available on this site.");

// Set new ward ID
$_SESSION['wardID'] = $id;

// Redirect.
header("Location: /directory");

?>
