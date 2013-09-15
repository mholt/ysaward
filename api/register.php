<?php
require_once("../lib/init.php");

// Ward password was entered before?
if (!isset($_SESSION['ward_id']) || $_SESSION['ward_id'] == 0)
	Response::Send(401);

// Um, if they already have an account and are logged in...
if (Member::IsLoggedIn())
	Response::Send(403);

// Create account (if everything checks out)
@ $email = $_POST['email'];
@ $hideEmail = isset($_POST['hideEmail']) ? 1 : 0;
@ $pwd1 = $_POST['pwd1'];
@ $pwd2 = $_POST['pwd2'];
@ $fname = $_POST['fname'];
@ $mname = $_POST['mname'];
@ $lname = $_POST['lname'];
@ $hideBirthday = isset($_POST['hideBirthday']) ? 1 : 0;
@ $dob = $_POST['dob'];
@ $gender = $_POST['gender'];
@ $resID = $_POST['resID'];
@ $aptnum = trim($_POST['aptnum']);
@ $streetAddress = $_POST['streetAddress'];
@ $city = $_POST['city'];
@ $state = $_POST['state'];
@ $zipcode = $_POST['zipcode'];
@ $phone = trim($_POST['phone']);
@ $hidePhone = isset($_POST['hidePhone']) ? 1 : 0;
@ $address = trim($_POST['address']);
@ $pic = $_FILES['profilepic'];

// Required fields filled out?
if (!$email || !$pwd1 || !$pwd2
	|| !$fname || !$lname || !$dob
	|| !$gender || !$resID)
	Response::Send(400, "Please fill out all required fields.");

$resIsCustom = $resID == "-";

// Make sure necessary apartment information is filled out
if (!$resIsCustom && !$aptnum
	|| ($resIsCustom && !$address))
	Response::Send(400, "Please be sure to fill out your apartment/address information.");

// Passwords match?
if ($pwd1 != $pwd2)
	Response::Send(400, "Your passwords don't match.");

// Password long enough?
if (strlen($pwd1) < 8)
	Response::Send(400, "Your password must be at least 8 characters.");

// Valid email?
if (!filter_var($email, FILTER_VALIDATE_EMAIL))
	Response::Send(400, "Please type a valid email address.");

// Picture uploaded OK? If not, it's probably too big
if ($pic['error'])
	Response::Send(413, "Try uploading a smaller profile picture. Max size: ".ini_get('upload_max_filesize')."B");

// Standardize name (remove accidental punctuation/typos)
$fname = trim(ucwords(preg_replace('/[^a-zA-Z() ]/', '', $fname)));
$mname = trim(ucwords(preg_replace('/[^a-zA-Z(). ]/', '', $mname)));
$lname = trim(ucwords(preg_replace('/[^a-zA-Z() ]/', '', $lname)));


if ($gender != Gender::Male && $gender != Gender::Female)
	Response::Send(400, "Sorry I have to ask, but are you male or female? Whatever you submitted wasn't valid.");


// More data validation...
// (passwords, email, gender, birthday, etc.)
// TODO: Prevent duplicate email accounts

// Phone number, if any, must include area code
if (strlen(preg_replace("/[^0-9A-Za-z]+/", "", $phone)) < 10 && strlen($phone) > 0)
	Response::Send(400, "Please type a full phone number, including area code. It should be 10 digits long.");

// Clean email and names
$fname = trim(str_replace(",", "", $fname));
$lname = trim(str_replace(",", "", $lname));
$email = trim(str_replace(" ", "", $email));
$email = str_replace(",", "", $email);


// Everything approved. Create account.
$m = new Member();
$m->WardID = $_SESSION['ward_id'];
$m->FirstName = $fname;
$m->MiddleName = $mname;
$m->LastName = $lname;
$m->Email = $email;
$m->HideEmail = $hideEmail;
$m->Gender = $gender;
$m->SetPassword($pwd1);
$m->PhoneNumber = $phone;
$m->HidePhone = $hidePhone;
$m->Birthday = sqldate($dob);
$m->HideBirthday = $hideBirthday;
$m->ReceiveEmails = true;
$m->ReceiveTexts = true;

// Fill out housing/Residence info
if (!$resIsCustom)
{
	$m->ResidenceID = $resID;
	$m->Apartment = strtoupper(preg_replace("/[^A-Za-z0-9 ]/", '', $aptnum));
}
else
{
	$ward = Ward::Load($_SESSION['ward_id']);
	$newRes = $ward->AddResidence("", $streetAddress, $city, $state, $zipcode, true);
	$m->ResidenceID = $newRes->ID();
	$m->Apartment = "";
}


// Pull the trigger: save the account.
// (Profile picture upload DOES happen; look down)
$m->Save();

// No need to 'register' anymore.
unset($_SESSION['ward_id']);

// Log member in so the survey can be filled out
$m->Login($email, $pwd1);

// Trigger to tell the user on the next page
// that they aren't done yet...!
// (Do this before the profile picture in case there's problems)
$_SESSION['isNew'] = true;

// Now upload and save the profile picture
if ($pic['tmp_name'])
	$m->PictureFile(false, $pic);


Response::Send(200);

?>