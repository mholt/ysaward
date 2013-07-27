<?php
require_once("lib/init.php");
protectPage(0, true);

// Based on the directory.php operations; Very similar.
// Instead of rendering an HTML table, we build a CSV file.
// Note that we don't actually create a file, save it to the server,
// and send it to the client. We only create a string that is
// sent to the client; it is never saved on the server.

// The filename of the to-be-downloaded file. Make safe and strip out common words
// (same as in the exportmembersmls.php file...)
$safeName = str_replace(" ", "_", strtolower($WARD->Name));
$safeName = preg_replace("/[^0-9A-Za-z_]+/", "", $safeName);
$safeName = preg_replace("/provo|utah|ysa|logan|ogden|orem|alpine|salt_lake_city|salt_lake|slc/", "", $safeName);
$safeName = trim($safeName, "_- ");
$filename = "{$safeName}_ward_directory.csv";

// ** Remember, that $MEMBER is defined in init.php and is the current, logged-in member.
// ** But it is only a non-null value if a member is logged in and not a stake leader.

// Get this member's privileges (viewing full or hidden info)
$allEmails = $MEMBER ? $MEMBER->HasPrivilege(PRIV_EXPORT_EMAIL) : true;	// Stake Leaders can see all info anyway
$allPhones = $MEMBER ? $MEMBER->HasPrivilege(PRIV_EXPORT_PHONE) : true;
$allBdays = $MEMBER ? $MEMBER->HasPrivilege(PRIV_EXPORT_BDATE) : true;


// Clerks, secretaries, EQ/RS presidency members, and bishopric can see site activity info
// for administrative purposes. Stake leaders can, too.
$showActivityInfo = $LEADER || ($MEMBER && $MEMBER->HasPresetCalling()) ? true : false;

// Get a list of all ward members
$q = "SELECT ID FROM Members WHERE WardID='{$WARD->ID()}' ORDER BY FirstName ASC, LastName ASC";
$r = DB::Run($q);

// Get a list of the questions this person is allowed to see
$permissions = $USER->Permissions(true);

// Our custom-built worky guy!
$csv = new CSVBuilder();

// Array of SurveyQuestion objects which this user can see
$questions = array(); 	// (populating in a moment)



// Add the fields to the header of the CSV file
$csv->AddField("First Name");
$csv->AddField("Middle Name");
$csv->AddField("Last Name");
$csv->AddField("Gender");
$csv->AddField("Home Address");
$csv->AddField("Mobile Phone");

if ($allEmails)
	$csv->AddField("Email Address");

$csv->AddField("Birthday");

if ($showActivityInfo)
{
	$csv->AddField("Registration Date");
	$csv->AddField("Last Updated");
	$csv->AddField("Last Activity");
}

// Build questions array based on permissions
foreach ($permissions as $per)
{
	$question = SurveyQuestion::Load($per->QuestionID());
	array_push($questions, $question);
	$csv->AddField($question->Question);
}



// We're done adding fields; now fill up the values row by row.



while ($row = mysql_fetch_array($r))
{
	$m = Member::Load($row['ID']);
	
	// Get parts of the birth date (so we can hide the year, if necessary)
	$bdate = strtotime($m->Birthday);
	$month = date("F", $bdate);
	$day = date("j", $bdate);
	$mm = date("m", $bdate);
	$dd = date("d", $bdate);
	$yyyy = date("Y", $bdate);

	// Prepare the member's last activity info in case we need to provide it
	$regDate = strtotime($m->RegistrationDate());
	$lastUpdated = strtotime($m->LastUpdated());
	$lastActivity = strtotime($m->LastActivity());
	
	
	// Add fields in the same order as the first line. Very important!
	$csv->AddValue($m->FirstName);
	$csv->AddValue($m->MiddleName);
	$csv->AddValue($m->LastName);
	$csv->AddValue(Gender::Render($m->Gender));
	$csv->AddValue($m->ResidenceString());
	$csv->AddValue(!$m->HidePhone || $allPhones ? formatPhoneForDisplay($m->PhoneNumber) : "");
	
	if ($allEmails)
		$csv->AddValue($m->Email);
	
	if ($allBdays)
		$csv->AddValue("{$mm}-{$dd}-{$yyyy}"); // works well with contact importers
	else
		$csv->AddValue(!$m->HideBirthday ? "{$month} {$day}" : ''); // doesn't work as well in contact importers. oh well.
	
	if ($showActivityInfo)
	{
		$csv->AddValue($regDate > 0 ? date("Y-m-d", $regDate) : "");
		$csv->AddValue($lastUpdated > 0 ? date("Y-m-d", $lastUpdated) : "");
		$csv->AddValue($lastActivity > 0 ? date("Y-m-d", $lastActivity) : "");
	}

	// Now, in the same order as questions saved (from above), we'll
	// add this member's answers to this line of the CSV file
	foreach ($questions as $question)
	{
		$ans = $question->Answers($m->ID());
		$csv->AddValue($ans ? strip_tags(trim($ans->ReadonlyAnswer())) : '');
	}
	
	$csv->NextRow();
}


// Send it to the browser as a download instead of displaying it
$csv->Download($filename);

?>