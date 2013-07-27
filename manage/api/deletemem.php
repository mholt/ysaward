<?php
require_once("../../lib/init.php");
protectPage(13);

if (!$_GET['id'] == $_SESSION['userID'])
	fail("Please specify a user ID to delete");

if ($_GET['id'] == $_SESSION['userID'])
	fail("ERROR > Sorry; you can't delete your own account (yet...)");

@$mem = Member::Load($_GET['id']);


if (!$mem)
	fail("That user couldn't be loaded. Are you sure the account exists?");


if ($mem->WardID != $MEMBER->WardID)
	fail("You can only delete accounts of members in your own ward.");


if (!$mem->Delete(true))
	fail("Could not delete member; probably forgot to be set confirmation flag to 'true', or bad ID was supplied.");


header("Location: ../../directory.php");

?>