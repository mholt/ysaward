<?php
require_once("../lib/init.php");
protectPage();


@ $wardid = $_POST['ward_id'];
@ $wardpwd = $_POST['wardpwd'];
@ $email = trim($_POST['email']);
@ $hideEmail = isset($_POST['hideEmail']) ? 1 : 0;
@ $oldpwd = $_POST['oldpwd'];
@ $pwd1 = $_POST['pwd1'];
@ $pwd2 = $_POST['pwd2'];
@ $fname = trim($_POST['fname']);
@ $mname = trim($_POST['mname']);
@ $lname = trim($_POST['lname']);
@ $dob = $_POST['dob'];
@ $hideBirthday = isset($_POST['hideBirthday']) ? 1 : 0;
@ $gender = $_POST['gender'];
@ $resID = $_POST['resID'];
@ $aptnum = trim($_POST['aptnum']);
@ $streetAddress = $_POST['streetAddress'];
@ $city = $_POST['city'];
@ $state = $_POST['state'];
@ $zipcode = $_POST['zipcode'];
@ $phone = trim($_POST['phone']);
@ $hidePhone = isset($_POST['hidePhone']) ? 1 : 0;
@ $receiveSms = isset($_POST['receiveSms']) ? 1 : 0;
@ $address = trim($_POST['address']);
@ $pic = $_FILES['profilepic'];

$isChangingWards = $WARD->ID() != $wardid && $wardid > 0;

// Required fields filled out?
if (!$email || !$fname || !$lname
	|| !$gender || !$resID || !$dob
	|| ($isChangingWards && !$wardpwd))
{
	Response::Send(400, "Please fill out all required fields.");
}

// If changing wards, make sure ward password is correct
if ($isChangingWards)
{
	$newWard = Ward::Load($wardid);
	if ($newWard != null && !$newWard->PasswordMatches($wardpwd))
		Response::Send(401, "Your new ward's password is incorrect. Please make sure you typed the ward password correctly.");
}

$newResIsCustom = $resID == "-";

// Make sure necessary Residence information is filled out
if (!$newResIsCustom && !$aptnum)
	Response::Send(400, "Oops - could you please fill out your apartment number? We need that. Thanks!");
if ($newResIsCustom && (!$address || !$streetAddress || !$city || !$state || !$zipcode))
	Response::Send(400, "Oops - could you please type your full address and click the little check-mark to verify it? We'll need to know where you live.");

// Passwords match?
if ($pwd1 && $pwd2 && $pwd1 != $pwd2 || ($pwd1 && !$pwd2))
	Response::Send(400, "Oops! Your passwords don't match. Please make sure they're the same in order to change your password.");

// Password long enough?
if ($pwd1 && strlen($pwd1) < 8)
	Response::Send(400, "Your password is too short. Could you try something longer? (at least 8 characters)");

// Phone number, if any, must include area code
if (strlen(preg_replace("/[^0-9A-Za-z]+/", "", $phone)) < 10 && strlen($phone) > 0)
	Response::Send(400, "Please type a full phone number, including area code. It should be 10 digits long.");

// Standardize name (remove accidental punctuation/numbers)
$fname = trim(ucwords(preg_replace('/[^a-zA-Z() ]/', '', $fname)));
$mname = trim(ucwords(preg_replace('/[^a-zA-Z(). ]/', '', $mname)));
$lname = trim(ucwords(preg_replace('/[^a-zA-Z() ]/', '', $lname)));


// Remove "hacky" strings from first name (for names like "AaaaaaaKaitlin") ...
// Sometimes members do this so their name appears at the top
$first3OfFName = strtolower(substr($fname, 0, 3));		// Get the first 3 characters (only 2 would break the logic on names like "Aaron")
if (preg_match("/(.)\\1{2,}/i", $first3OfFName))		// If first 3 characters are the same...
	$fname = ucwords(preg_replace("/(.)\\1{2,}/i", '', $fname, 1)); 	// ... then remove all the repeated characters at the beginning.


if (!filter_var($email, FILTER_VALIDATE_EMAIL))
	Response::Send(400, "Please type a valid email address.");

if ($gender != Gender::Male && $gender != Gender::Female)
	Response::Send(400, "Excuse me, but are you male or female? Whatever you chose wasn't valid.");

// Clean email and names
$fname = trim(str_replace(",", "", $fname));
$lname = trim(str_replace(",", "", $lname));
$email = trim(str_replace(" ", "", $email));
$email = str_replace(",", "", $email);


// Everything approved. Save the profile
$MEMBER->FirstName = $fname;
$MEMBER->MiddleName = $mname;
$MEMBER->LastName = $lname;
$MEMBER->Email = $email;
$MEMBER->HideEmail = $hideEmail;
$MEMBER->Gender = $gender;
if ($pwd1 && $pwd2) $MEMBER->ChangePassword($pwd1, $oldpwd);
$MEMBER->PhoneNumber = $phone;
$MEMBER->HidePhone = $hidePhone;
$MEMBER->ReceiveTexts = $receiveSms;
$MEMBER->Birthday = sqldate($dob);
$MEMBER->HideBirthday = $hideBirthday;

// Here follows the logic of the member's Residence.
// If there's a custom Residence, it has no "Name"
// and the Member's "Apartment" field is blank too.
// If it's a regular residence, it has a "Name",
// and the "Apartment" field has the unit number.

function setNewCustomResidence(&$MEMBER, &$WARD, $streetAddress, $city, $state, $zipcode)
{
	$newRes = $WARD->AddResidence("", $streetAddress, $city, $state, $zipcode, true);
	$MEMBER->ResidenceID = $newRes->ID();
	$MEMBER->Apartment = "";
}

function setNewRegularResidence(&$MEMBER, $resID, $aptnum)
{
	$MEMBER->ResidenceID = $resID;
	$MEMBER->Apartment = strtoupper(preg_replace("/[^A-Za-z0-9 ]/", '', $aptnum));
}

$res = $MEMBER->Residence();	// Current Residence

if (!$res || !$res->Custom())
{
	// Turns out the logic for if they previously had no residence
	// or if they had a regular one is the exact same.
	// Just update the ID and apt. number, or create a custom residence...
	if (!$newResIsCustom)
		setNewRegularResidence($MEMBER, $resID, $aptnum);
	else
		setNewCustomResidence($MEMBER, $WARD, $streetAddress, $city, $state, $zipcode);
}
else 	// But if the previous Residence IS custom...
{
	// ... and they still have a custom residence...
	if ($newResIsCustom)
	{
		// ... delete the old one and replace it with the new one, only if it's different.
		if ($streetAddress != $res->Address || $city != $res->City
			|| $state != $res->State || $zipcode != $res->PostalCode)
		{
			$res->Delete(true);
			setNewCustomResidence($MEMBER, $WARD, $streetAddress, $city, $state, $zipcode);
		}
	}
	else
	{
		// If the new Residence is regular, delete the old custom one and fill the regular values
		$res->Delete(true);
		setNewRegularResidence($MEMBER, $resID, $aptnum);
	}
}

// Pull the trigger: save the account changes
// (Profile picture upload DOES happen; look down)
$MEMBER->Save(true);

// Now upload and save the profile picture...
if ($pic['tmp_name'])
	$MEMBER->PictureFile(false, $pic);

if ($isChangingWards)
{
	$MEMBER->ChangeWard($wardid);
	Response::Send(200, "Saved your profile and switched wards!");
}
else
	Response::Send(200, "Saved your profile!");

?>