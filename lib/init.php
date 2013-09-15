<?php
/*	
	VERY IMPORTANT FILE...
	Required by every web page on the site
	for basic functionality. Be careful in here.
	(Not used, however, by CLI-invoked scripts)
*/
// Start session... we'll need it
session_start();

require_once "common.php";

// Open a persistent connection to the database
$DB = new DB();

// If the user is logged in, update last activity.
// They could be a leader or a regular member.
$MEMBER = Member::Current();
$LEADER = null;
$WARD = null;
if ($MEMBER)
	$MEMBER->UpdateLastActivity();
else
{
	$LEADER = StakeLeader::Current();
	if ($LEADER)
		$LEADER->UpdateLastActivity();
}
if ($MEMBER)
	$WARD = Ward::Load($MEMBER->WardID);
else if ($LEADER)
	$WARD = Ward::Load($_SESSION['wardID']);
$USER = $MEMBER ? $MEMBER : $LEADER;

define('IS_MOBILE', isMobile());

?>