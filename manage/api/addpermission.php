<?php
require_once("../../lib/init.php");
protectPage(9);

@ $callingID = $_POST['callingID'];
@ $memberID = $_POST['memberID'];
@ $questionID = $_POST['questionID'];
$allMembers = isset($_POST['allMembers']) ? $_POST['allMembers'] : null;
$allCallings = isset($_POST['allCallings']) ? $_POST['allCallings'] : null;

// Can't set a permission for ALL callings AND members together
if ($allMembers && $allCallings)
	fail("Sorry, can't set a permission for all members <i>and</i> all callings. It's redundant!");

// Can't set a permission for both a calling and member together
if ($callingID && $memberID)
	fail("Oops. Please select only a member OR a calling to grant permissions for.");

// They have to select at least one of those, however
if (!$callingID && !$memberID && !$allMembers && !$allCallings)
	fail("Oops! No calling or member selected. Please select <i>either</i> a member <i>or</i> a calling to grant permissions for.");

// And a question is, of course, required.
if (!$questionID)
	fail("Nothing to do; don't forget to choose at least one question for which to grant permission.");

// If they chose a wildcard, make sure the selection of a member
// or calling is not set (as a safety)
if ($allMembers && $memberID)
	fail("You selected to set this permission for ALL members but chose a specific member. Which one? Please go back and try again.");
if ($allCallings && $callingID)
	fail("You selected to set this permission for ALL callings but chose a specific calling. Which one? Please go back and try again.");
if (($allMembers && $callingID) || ($allCallings && $memberID))
	fail("You chose a wildcard permission across all callings or members but also chose a specific member or calling. Please select only one or the other.");


// Make sure the selected member or calling is in this ward
if ($callingID)
{
	$c = Calling::Load($callingID);
	if ($c->WardID() != $MEMBER->WardID)
		fail("The calling you chose is not in your ward.");
}
else if ($memberID)
{
	$m = Member::Load($memberID);
	if ($m->WardID != $MEMBER->WardID)
		fail("The member you chose is not in your ward.");
}


$objID = $callingID ? $callingID : $memberID;
$objType = $callingID ? "Calling" : "Member";

$n = count($questionID);

for($i = 0; $i < $n; $i++)
{
	$p = new Permission();
	$p->QuestionID($questionID[$i]);
	$p->Allow($objID, $objType, true);
}

// Must do a redirect because this form isn't ajax-ified...
header("Location: ../permissions.php");

?>