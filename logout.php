<?php
require_once("lib/init.php");

// Make sure they're first logged in
protectPage(0, true);

if ($MEMBER)
{
	// Perform member logout
	if (!Member::Logout())
	{
		// Uh oh.
		// Attempt to perform manual, "hard-wired" logout...
		$_SESSION['userID'] = 0;
		if (isset($_SESSION['userID']))
			unset($_SESSION['userID']);
		session_destroy();
	}
}
else
{
	// Perform leader logout
	if (!StakeLeader::Logout())
	{
		// Same spiel as above...
		$_SESSION['stakeLeaderID'] = 0;
		if (isset($_SESSION['stakeLeaderID']))
			unset($_SESSION['stakeLeaderID']);
		session_destroy();
	}
}

header("Location: /");

?>