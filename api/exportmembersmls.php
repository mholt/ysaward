<?php
require_once("../lib/init.php");
protectPage();


// TODO: Ensure the user is a clerk, secretary, or bishopric member
// Right now we're only checking to see if they have a "preset" calling
// which includes EQP and RSP. (Also see ../exportmls.php for this)
if (!$MEMBER || !$MEMBER->HasPresetCalling())
{
	header("Location: /directory");
	exit;
}

@ $yyyy = $_POST['year'];
@ $mm = $_POST['month'];
@ $dd = $_POST['day'];

if (!$yyyy || !$mm || !$dd || $yyyy < 2011
	|| $yyyy > date("Y") || $mm < 1 || $mm > 12
	|| $dd < 1 || $dd > 31 || !is_numeric($yyyy)
	|| !is_numeric($mm) || !is_numeric($dd))
{
	fail("Please be sure to select a cutoff date: day, month, and year.");
}


// The filename of the to-be-downloaded file. Make safe and strip out common words
$safeName = str_replace(" ", "_", strtolower($WARD->Name));
$safeName = preg_replace("/[^0-9A-Za-z_]+/", "", $safeName);
$safeName = preg_replace("/provo|utah|ysa|logan|ogden|orem|alpine|salt_lake_city|slc|salt_lake/", "", $safeName);
$safeName = trim($safeName, "_- ");
$filename = "{$safeName}_mls.csv";


// Run query; prepare to use results
$q = DB::Run("SELECT ID FROM Members WHERE WardID='{$WARD->ID()}' AND RegistrationDate >= '{$yyyy}-{$mm}-{$dd}' ORDER BY RegistrationDate ASC");


// Prepare the csv file
$csv = new CSVBuilder();

// Fields for the header of the file
$csv->AddField("Name");
$csv->AddField("Birth Date");
$csv->AddField("Address");
$csv->AddField("City");
$csv->AddField("State");
$csv->AddField("Postal");
$csv->AddField("Phone");
$csv->AddField("Prior Unit");


// Add all the data to the file
while ($r = mysql_fetch_array($q))
{
	$m = Member::Load($r['ID']);
	$res = $m->Residence();

	// Get parts of the birth date so we can put in that funky MLS format
	$bdate = strtotime($m->Birthday);
	$month = date("m", $bdate);
	$day = date("d", $bdate);
	$year = date("Y", $bdate);

	// If the address isn't custom, the street portion needs to have the unit number appended
	$streetAddr = $res ? $res->Address : "";
	if ($res && !$res->Custom() && trim($m->Apartment) != "")
		$streetAddr .= " Apt ".$m->Apartment;

	// Build this row
	$csv->AddValue($m->LastName.", ".$m->FirstName);
	$csv->AddValue($year.$month.$day);
	$csv->AddValue($streetAddr);
	$csv->AddValue($res ? $res->City : "");
	$csv->AddValue($res ? $res->State : "");
	$csv->AddValue($res ? $res->PostalCode : "");
	$csv->AddValue(formatPhoneForDisplay($m->PhoneNumber));
	$csv->AddValue("");

	$csv->NextRow();
}


// Send file as a download
$csv->Download($filename);

?>