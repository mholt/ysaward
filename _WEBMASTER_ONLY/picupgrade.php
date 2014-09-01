<?php
/*
	Performs resizing operations on all profile pictures.
	This was originally used to bring pictures up from a small thumb/medium
	size to a larger size (about 2x) fit for retina/high-DPI displays.

	Protect this file if you upload it. It has no security built-in.
*/

exit;	// SAFETY LINE; disable to use this file



require_once "../lib/init.php";

echo "<pre>";

set_time_limit(0);

$mems = DB::Run("SELECT ID FROM Members ORDER BY ID ASC");

$i = 0;
while ($row = mysql_fetch_array($mems))
{
	$mem = Member::Load($row['ID']);
	
	if (!$mem->PictureFile)
		continue;

	$picFile = $mem->PictureFile;
	$main = filename($mem->PictureFile);
	$ext = extension($mem->PictureFile, "jpg");
	$newRand = rand(1000, 9999);

	$newMain = $mem->FirstName."_".$mem->LastName."_".$mem->ID()."_".$newRand;

	$newFull = $newMain.".".$ext;
	$newMedium = $newMain."_med.".$ext;
	$newThumb = $newMain."_thumb.".$ext;

	echo "PICTURE:\n$newMain\n$newFull\n$newMedium\n$newThumb\n";

	copy("uploads/".$mem->PictureFile, "uploads/".$newFull);
	create_jpgthumb("uploads/".$mem->PictureFile, "uploads/".$newMedium, Member::MEDIUM_DIM, Member::MEDIUM_DIM, 85, true);
	create_jpgthumb("uploads/".$mem->PictureFile, "uploads/".$newThumb, Member::THUMB_DIM, Member::THUMB_DIM, 70, false);

	$mem->DeletePictureFile();
	$mem->PictureFile = $newFull;
	$mem->Save();
	
	$i++;
	echo "DONE.\n\n";
}

echo "Updated $i members' pictures.\n";
echo "</pre>";
?>