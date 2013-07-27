<?php
require_once("../lib/init.php");
protectPage(12);	// Profile pictures privileges

// Get a list of all current members
$q = "SELECT ID FROM Members WHERE WardID={$MEMBER->WardID} AND PictureFile != '' ORDER BY FirstName ASC, LastName ASC";
$r = DB::Run($q);

if (mysql_num_rows($r) == 0)
	fail("No pictures to export; no members have a profile picture.");


$zip = new ZipStream("profile_pics.zip");

while ($row = mysql_fetch_array($r))
{
	$member = Member::Load($row['ID']);
	$file = $member->PictureFile;
	if (file_exists("../uploads/{$file}"))
		$zip->addLargeFile("../uploads/".$file, "profile_pictures/".$file);
}

$zip->finalize();
?>