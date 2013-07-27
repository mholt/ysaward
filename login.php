<?php
require_once("lib/init.php");

@ $eml = trim($_POST['eml']);
@ $pwd = trim($_POST['pwd']);

// Login; returns null if bad credentials.
// First see if they're a regular member...

$m = Member::Login($eml, $pwd);

if (!$m)
{
	// No? Maybe a stake leader?
	$s = StakeLeader::Login($eml, $pwd);

	if (!$s)
		Response::Send(400);	// Evidently not. Login failed.
	else
	{
		// Choose the first ward in the stake... alphabetically I guess... as default view for them.
		$r = mysql_fetch_array(DB::Run("SELECT ID FROM Wards WHERE StakeID='{$s->StakeID}' AND Deleted != 1 ORDER BY Name ASC LIMIT 1"));
		$_SESSION['wardID'] = $r['ID'];
		
		// Stake leader logged in.
		Response::Send(200);
	}
}
else
	Response::Send(200);		// Member login

?>
