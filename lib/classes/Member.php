<?php

/**
 * @author Matthew Holt
 */

class Member
{
	private $ID = 0;
	private $CredentialsID;
	public $WardID;
	public $FirstName;
	public $MiddleName;
	public $LastName;
	public $Gender;
	public $PhoneNumber;
	public $ResidenceID;
	public $Apartment;
	public $Birthday;
	public $PictureFile;
	private $LastUpdated;
	private $LastActivity;
	private $RegistrationDate;
	public $HidePhone;
	public $HideEmail;
	public $HideBirthday;
	public $FheGroup;
	public $Email;
	public $ReceiveEmails;
	public $ReceiveTexts;
	private $Password;
	private $Salt;

	const MAX_DISPLAY_DIM = 250;		// Max height or width of full pic (for display on the site)
	const THUMB_DIM = 100;				// Square size of picture thumbnail
	const THUMB_DIM_MOBILE = 55;		// Square size of picture thumbnail on mobile devices

	public function __construct()
	{
		if (!$this->ID) // new members only
			$this->RegistrationDate = now();
	}

	// Loads a member, instantiates it, and returns it.
	public static function Load($id)
	{
		$id = DB::Safe($id);
		$q = "SELECT Members.*, Credentials.Email, Credentials.Password, Credentials.Salt FROM Members INNER JOIN Credentials ON Members.ID = Credentials.MemberID WHERE Members.ID='$id' LIMIT 1";
		$r = DB::Run($q);
		if (!$r)
			fail(mysql_error());
		$m = mysql_fetch_object($r, 'Member');
		return $m;
	}

	// Loads the currently logged-in member object
	public static function Current()
	{
		if (!isset($_SESSION['userID']))
			return null;

		return Member::Load($_SESSION['userID']);
	}

	/**
	 * Logs this member into the session given username and password
	 * @var $eml = The email address
	 * @var $pwd = The plaintext password (will be salted and hashed)
	 * @return If successful, the Member object. Otherwise null.
	*/
	public static function Login($eml, $pwd)
	{
		// Sanitize input
		$eml = DB::Safe($eml);

		// First, we need to obtain this member's unique salt
		$r = DB::Run("SELECT Salt FROM Credentials WHERE Email='$eml' AND MemberID > 0 LIMIT 1");
		if (mysql_num_rows($r) == 0)
			return null;

		$salt = mysql_result($r, 0);

		// Now hash input according to our hashing algorithm and user's salt
		$pwd = hashPwd($pwd, $salt);

		// See if the email/password combination are correct
		$try = DB::Run("SELECT MemberID FROM Credentials WHERE Email='$eml' AND Password='$pwd' AND MemberID > 0 LIMIT 1");
		if (mysql_num_rows($try) == 0)
			return null;

		// At this point, valid credentials were entered. Proceed...
		$memberID = mysql_result($try, 0);
		$member = Member::Load($memberID);

		// Update LastActivity
		$member->UpdateLastActivity();

		// Since they've logged in, no more need for existing
		// password reset tokens. Delete any strays for security.
		$q = "DELETE FROM PwdResetTokens WHERE CredentialsID={$member->CredentialsID()}";
		DB::Run($q);

		// Save the session. This is the actual "logging in" part.
		session_regenerate_id();			// Helps prevent session hijacking
		$_SESSION["userID"] = $memberID;
		$_SESSION["timestamp"] = time();
		$_SESSION["ipaddress"] = $_SERVER['REMOTE_ADDR'];
		return $member;
	}

	// Logs out the current user.
	public static function Logout()
	{
		$_SESSION = array();
		session_destroy();
		session_start();
		session_regenerate_id();
		return !isset($_SESSION['userID']);
	}

	// Returns true or false if the user is logged in or not
	public static function IsLoggedIn()
	{
		// To prevent possible session hijacking, compare IP addresses
		// from what they logged in with to what the current client has.
		// If it's different, the session ID was probably intercepted.
		// In that case, do a full, deliberate logout.
		if (isset($_SESSION['ipaddress']) && $_SESSION['ipaddress'] != $_SERVER['REMOTE_ADDR'])
			Member::Logout();

		return isset($_SESSION['userID'])
			&& isset($_SESSION['ipaddress'])
			&& isset($_SESSION['timestamp'])
			&& $_SESSION['userID'] > 0
			&& $_SESSION['ipaddress'] == $_SERVER['REMOTE_ADDR'];
	}

	// Gets the ID of this member
	public function ID()
	{
		return $this->ID;
	}

	// Gets the Credentials ID of this member
	public function CredentialsID()
	{
		return $this->CredentialsID;
	}

	// Create or update this instance. Returns true or false.
	// If false is supplied, the LastUpdated value won't change (default)
	// If true is supplied, the LastUpdated timestamp WILL change.
	public function Save($updateLastUpdated = false)
	{
		// A valid ward ID is required.
		if (!Ward::Load($this->WardID))
			fail("Cannot save account information for {$this->Email} -- a valid ward ID is required ({$this->WardID} is not valid).");

		// Make sure the email address and the email ACCOUNT is unique (foo+a@bar.com is not unique to foo+b@bar.com)
		$this->Email = trim($this->Email);
		$q = "SELECT 1 FROM Credentials WHERE Email='$this->Email' AND ID!='$this->CredentialsID' LIMIT 1";
		if (mysql_num_rows(DB::Run($q)) > 0)
			fail("Could not save account info for $this->Email. That email address is already in use by another member.");

		// Standardize the phone number
		$this->PhoneNumber = preg_replace("/[^0-9A-Za-z]+/", "", $this->PhoneNumber);
		$this->PhoneNumber = strtoupper($this->PhoneNumber);

		// Turn it into number-only (e.g. 123-6454 instead of 123-PINK) -- some phones don't have letters with the digits
		$this->PhoneNumber = phoneAlphaToNumeric($this->PhoneNumber);

		// For EmailJobs, make sure name and email has no delimiting characters.
		// (Just trim them out; validation should have already occurred.)
		$this->Email = str_replace("=", "", $this->Email);
		$this->Email = str_replace(",", "", $this->Email);
		$this->FirstName = str_replace("=", "", $this->FirstName);
		$this->FirstName = str_replace(",", "", $this->FirstName);
		$this->LastName = str_replace("=", "", $this->LastName);
		$this->LastName = str_replace(",", "", $this->LastName);

		if ($updateLastUpdated)
			$this->LastUpdated = now();

		// Prepare to save this object. It goes in two parts: Credentials and Member data.
		// The BuildCredentialsSaveQuery function will remove the fields which are not
		// in the Members table, after using them in building the query.
		$objectVars = get_object_vars($this);

		$q = DB::BuildCredentialsSaveQuery($this, $objectVars);
		$r = DB::Run($q);
		if (!$this->CredentialsID)
			$this->CredentialsID = mysql_insert_id();
		
		$q = DB::BuildSaveQuery($this, $objectVars);
		$r = DB::Run($q);
		if (!$this->ID)
		{
			$this->ID = mysql_insert_id();
			return $this->Save();
		}
		return $r ? true : false;
	}

	// Associate a calling for this member. Pass in the ID of the Calling.
	// Be careful doing this. This gives members special access to private info.
	public function AddCalling($callingID)
	{
		if (!$callingID || !$this->ID)
			return false;

		// Make sure the calling assignment is unique
		$q = "SELECT 1 FROM MembersCallings WHERE MemberID='$this->ID' AND CallingID='$callingID' LIMIT 1";
		if (mysql_num_rows(DB::Run($q)) > 0)
			fail("Member (ID ".$this->ID.") already has that calling (ID ".$callingID.")");

		// Make sure the calling exists in the member's same ward
		$c = Calling::Load($callingID);
		if (!$c || $c->WardID() != $this->WardID)
			return false;

		// Make sure the calling isn't Bishop when a bishop is already assigned...
		if ($c->Name == "Bishop" && count($c->Members()) > 0)
			fail("A bishop is already assigned in the ward. A ward cannot have two bishops at once...");

		// Perform the insert
		$q = "INSERT INTO MembersCallings (MemberID, CallingID) VALUES ($this->ID, $callingID)";
		if (!DB::Run($q))
			fail("Could not associate member with calling: " . mysql_error());

		return true;
	}

	// Remove a calling from this member's assignment
	public function RemoveCalling($callingID)
	{
		if (!$callingID || !$this->ID)
			return false;

		$q = "DELETE FROM MembersCallings WHERE MemberID='$this->ID' AND CallingID='$callingID' LIMIT 1";
		if (!DB::Run($q))
			fail("Could not remove calling from member: " . mysql_error());

		return mysql_affected_rows() > 0 ? true : false;
	}


	// Returns permissions this member has based on the callings associated and also member-ID-based permissions.
	// Permission objects are returned in an array.
	// ** WARNING: PERMISSIONS MAY OVERLAP. (Multiple permissions apply to a single question)
	// ** PASS IN BOOLEAN true TO REMOVE OVERLAP. (Only this user's first permission for that question is kept.)
	public function Permissions($removeOverlap = false)
	{
		$permissions = array();

		// Get permissions based on member ID...
		// Be careful changing this because we append later!
		$q = "SELECT ID FROM Permissions WHERE (ObjectID='$this->ID' AND ObjectType='Member') OR (";

		// Now permissions based on callings for this member
		$callings = $this->Callings();
		foreach ($callings as $calling)
			$q .= "ObjectID=".$calling->ID()." OR ";

		// Trim the trailing OR bit...
		if (strrpos('(', $q) == strlen($q) - 1)
			$q = substr($q, 0, strlen($q) - 5);
		else
			$q = substr($q, 0, strlen($q) - 4);

		if (count($callings) > 0)
			$q .= " AND ObjectType='Calling')"; // don't forget this

		$r = DB::Run($q);

		while ($row = mysql_fetch_array($r))
		{
			$per = Permission::Load($row['ID']);

			// Prevent duplicates questions/overlap of permissions?
			if ($removeOverlap)
			{
				$found = false;
				foreach ($permissions as $p)
				{
					if ($p->QuestionID() == $per->QuestionID())
					{
						$found = true;
						break;
					}
				}
				if ($found) continue;
			}

			$permissions[] = $per;
		}

		return $permissions;
	}

	// Returns an array of Calling objects which belong to this member
	// In order by ID ascending (generally, oldest first)
	public function Callings()
	{
		$q = "SELECT CallingID FROM MembersCallings WHERE MemberID='$this->ID' ORDER BY ID ASC";
		$r = DB::Run($q);

		$callings = array();

		while ($row = mysql_fetch_array($r))
			$callings[] = Calling::Load($row['CallingID']);

		return $callings;
	}


	// Returns true if this member has any calling which is a built-in, preset calling.
	public function HasPresetCalling()
	{
		$callings = $this->Callings();

		foreach ($callings as $c)
			if ($c->Preset())
				return true;

		return false;
	}

	// Returns true or false, indicating if this member
	// (either as a member or according to his/her calling)
	// has a privilege to do something specified by the
	// ID of the privilege.
	public function HasPrivilege($privID)
	{
		$q = "SELECT `ID` FROM `GrantedPrivileges` WHERE (MemberID={$this->ID}";

		$callings = $this->Callings();
		foreach ($callings as $calling)
			$q .= " OR CallingID={$calling->ID()}";

		$q .= ") AND PrivilegeID={$privID}";

		$r = DB::Run($q);
		return mysql_num_rows($r) > 0;
	}


	// Returns true if any privilege in the Manage menu (IDs between 7 and 13)
	// has been granted to this user, by Member or by Calling (like the HasPrivilege function)
	public function HasAnyManagePrivilege()
	{
		$q = "SELECT 1 FROM `GrantedPrivileges` WHERE (MemberID={$this->ID}";

		$callings = $this->Callings();
		foreach ($callings as $calling)
			$q .= " OR `CallingID`={$calling->ID()}";
		$q .= ") AND `PrivilegeID` BETWEEN 7 AND 13";
		
		$r = DB::Run($q);
		return mysql_num_rows($r) > 0;
	}


	// Changes password. Requires new plaintext password.
	// If resetting the password, you only need to pass in
	// the first argument, but for a deliberate change, both
	// are needed.
	// SAVES THE MEMBER OBJECT and returns the result of the save.
	public function ChangePassword($newPwd, $oldPwd = null)
	{
		if (!$newPwd)
			return false;

		// If no oldPwd is passed in, make sure there is permission to
		// simply CHANGE the password (a valid reset token)... then do it.
		if (!isset($oldPwd))
		{
			$q = "SELECT 1 FROM PwdResetTokens WHERE CredentialsID=$this->CredentialsID LIMIT 1";
			if (mysql_num_rows(DB::Run($q)) == 0)
				fail("Can't reset password without old password; no password reset token has been set for this user. To set one, use the Forgot Password link.");

			$this->SetNewPassword($newPwd);

			// Delete the reset token...
			$q = "DELETE FROM PwdResetTokens WHERE CredentialsID=$this->CredentialsID";
			DB::Run($q);

			return $this->Save();
		}
		else
		{
			// Change password
			$oldPwd = hashPwd($oldPwd, $this->Salt);

			// Make sure old password is correct
			$q = "SELECT 1 FROM Credentials WHERE ID='$this->CredentialsID' AND Password='$oldPwd' LIMIT 1";
			$r = DB::Run($q);

			// Save new password
			if (mysql_num_rows($r) > 0 || $this->Password = '')
			{
				$this->SetNewPassword($newPwd);
				return $this->Save();
			}
			else
				fail("Could not change your password because the old password is incorrect.");
		}
	}

	// Sets/gets the profile picture. Pass $thumb = true to get
	// the filename of the thumbnail. Default method call PictureFile()
	// will return filename of the "full" image. Pass a file
	// straight from $_FILES array on upload to set a new picture.
	// If setting, it SAVES the instance.
	public function PictureFile($thumb = false, $newpic = null)
	{
		if (!$this->ID)
			fail("Can't set profile picture when this user doesn't have an ID.");

		$path = "uploads"; 	// Path to store the uploaded pics relative to doc root

		if ($newpic)
		{
			$filename = $newpic['name'];
			$tmppath = $newpic['tmp_name'];
			$type = $newpic['type'];
			$size = $newpic['size'];

			if (!$tmppath)
				fail("No valid uploaded file found for $filename... please report this.");

			// Get extension (excluding the '.')
			$ext = extension($filename);

			// Extension and filetype valid?
			if ((strtolower($ext) != "jpg" && strtolower($ext) != "jpeg") || $type != "image/jpeg")
				fail("You are only allowed to upload JPG files, nothing else. You tried to upload a $type file ($filename).");

			// Exact size of 2 MB is 2,097,152 bytes
			if ($size > 2097152)
				fail("The file you selected ($filename) is too large. It must be under 2 MB.");

			// Random number to append to filename (helps with browser caching issues)
			$rnd = rand(1000, 9999);

			$newpath = DOCROOT."/{$path}/";
			$newfilename = "{$this->FirstName}_{$this->LastName}_{$this->ID}_{$rnd}.{$ext}";
			$newfilenameThumb = "{$this->FirstName}_{$this->LastName}_{$this->ID}_{$rnd}_thumb.{$ext}";
			$newfilenameMedium = "{$this->FirstName}_{$this->LastName}_{$this->ID}_{$rnd}_med.{$ext}";

			// Make filenames safe
			$newfilename = preg_replace('/[^a-zA-Z0-9_.()]/', '', $newfilename);
			$newfilenameThumb = preg_replace('/[^a-zA-Z0-9_.()]/', '', $newfilenameThumb);
			$newfilenameMedium = preg_replace('/[^a-zA-Z0-9_.()]/', '', $newfilenameMedium);

			// Delete old files, since the filename will probably be different
			$this->DeletePictureFile();

			// Create and save new profile pictures.
			create_jpgthumb($tmppath, $newpath.$newfilenameMedium, Member::MAX_DISPLAY_DIM, Member::MAX_DISPLAY_DIM, 80); // Medium for display on site
			create_jpgthumb($tmppath, $newpath.$newfilenameThumb, Member::THUMB_DIM, Member::THUMB_DIM, 75, false);	  // Thumbnail
			move_uploaded_file($tmppath, $newpath.$newfilename);

			// Save database row
			$this->PictureFile = $newfilename;
			$this->Save();
		}
		else
		{
			if (!$this->PictureFile)
			{
				if ($this->Gender == Gender::Male)
					return "/resources/images/brother.png";
				else
					return "/resources/images/sister.png";
			}
			$main = filename($this->PictureFile);
			$ext = extension($this->PictureFile);
			return "/$path/".($thumb ? $main."_thumb.".$ext : $main.'_med.'.$ext);
		}
	}

	// Returns the HTML "img" tag of this member's profile pic.
	// For a maximum size, pass in $maxDimension; it will not be larger than it either way.
	// By default, returns medium-sized picture, not thumbnail; specify true for thumbnail.
	// Set $lazy to false if you want the image to load right away (not just when the user scrolls to it)
	public function ProfilePicImgTag($thumb = false, $lazy = true, $maxDimension = 0)
	{
		$picFile = $this->PictureFile($thumb);
		
		if (!$maxDimension)
			$maxDimension = $thumb ? Member::THUMB_DIM : Member::MAX_DISPLAY_DIM;

		if ($lazy)
			return '<img src="/resources/images/loader.gif" data-src="'.$picFile.'" alt="'.$this->FirstName.'\'s picture" style="max-width: '.$maxDimension.'px; max-height: '.$maxDimension.'px;" class="profilePicture">';
		else
			return '<img src='.$picFile.'" alt="'.$this->FirstName.'\'s picture" style="max-width: '.$maxDimension.'px; max-height: '.$maxDimension.'px;" class="profilePicture">';
	}

	// Deletes the picture file and thumbnail for this member.
	// Also updates database. Returns false if failure.
	public function DeletePictureFile()
	{
		if (!$this->PictureFile)
			return false;
		$main = filename($this->PictureFile);
		$ext = extension($this->PictureFile);
		$thumb = $main."_thumb.".$ext;
		$med = $main."_med.".$ext;
		$this->PictureFile = '';
		$result = @unlink(DOCROOT."/uploads/".$main.'.'.$ext) && @unlink(DOCROOT."/uploads/".$thumb) && @unlink(DOCROOT."/uploads/".$med);
		if ($result)
			$this->Save();
		return $result;
	}

	// If this is a new user and no password is set, create one.
	// (Also generates a salt for this user)
	// Does NOT auto-save.
	public function SetPassword($pwd)
	{
		if (!$this->Password)
		{
			$this->SetNewPassword($pwd);
			return true;
		}
		else
			return false;
	}


	// Gets the number of successful text messages sent within 24 hours, including unfinished jobs
	public function TextMessagesSentInLastDay()
	{
		$count = 0;
		$q = DB::Run("SELECT Recipients, SegmentCount, FailedRecipients
					FROM SMSJobs
					WHERE (Finished > NOW() - INTERVAL 1 DAY OR Started > NOW() - INTERVAL 1 DAY)
						AND WardID='{$this->WardID}'
						AND SenderID='{$this->ID}'");

		while ($row = mysql_fetch_array($q))
		{
			$recip = json_decode($row['Recipients']);
			$parts = $row['SegmentCount'];
			$failed = json_decode($row['FailedRecipients']);
			$count += count($recip) * $parts - count($failed);
		}
		return $count;
	}


	// Deletes a member and all info associated with it.
	// $sure must be set to boolean true to be safe.
	// Returns true if successful; false or dies otherwise.
	public function Delete($sure = false)
	{
		// Safety
		if ($sure !== true || !$this->ID)
			return false;

		$this->DeletePictureFile();

		$this->DeleteWardItems();

		// Delete credentials
		$q = "DELETE FROM Credentials WHERE ID='$this->CredentialsID'";
		if (!DB::Run($q))
			fail("Deleted picture, permissions, callings, privileges, password reset tokens, and survey answers, but not credentials (or user account) (user can still login): ".mysql_error());

		// Delete member record
		$q = "DELETE FROM Members WHERE ID='$this->ID' LIMIT 1";
		if (!DB::Run($q))
			fail("Deleted everything for this member except the record itself (they still have an account but CANNOT login), problem - ".mysql_error());

		return true;
	}

	// Returns true if this member has a custom residence
	// (one that's not "standard" to the ward, like if they live outside of ward boundaries)
	public function HasCustomResidence()
	{
		$res = $this->Residence();
		return $res ? $this->Residence()->Custom() : false;
	}

	// Returns this member's Residence object
	public function Residence()
	{
		return Residence::Load($this->ResidenceID);
	}

	// Returns a "simple" string of this Member's Residence
	// (e.g. "Stratford 203" if regular, or "134 S Palisades Dr Orem UT" if custom)
	public function ResidenceString()
	{
		$res = $this->Residence();
		
		if (!$res)
			return "";
		elseif ($res->Custom())
			return $res->Address." ".$res->City." ".$res->State;
		else
			return $res->Name." ".$this->Apartment;
	}

	// Changes the member's ward to a different ward, given a ward ID.
	// It's almost like deleting the account and re-creating it in the new
	// ward... even the RegistrationDate is updated (registration date =
	// date registered in that ward)
	public function ChangeWard($wardid)
	{
		if (dayDifference($this->RegistrationDate) < 7)
			fail("Cannot change wards within 7 days of being in a new ward.");
		
		$new_ward = Ward::Load($wardid);
		if (!$new_ward)
			fail("Could not change ward: ward ID $wardid not valid.");
		$this->DeleteWardItems();
		$this->WardID = $wardid;
		$this->RegistrationDate = now();
		$this->LastUpdated = 0;
		$this->Save();
	}

	// Update LastActivity timestamp. This method is error-suppressed because it's
	// not important enough that it should interfere with page loads ('cept the security part)
	public function UpdateLastActivity()
	{
		if (isset($_SESSION['timestamp']) && $_SESSION['timestamp'] - time() > 60 * 10)
		{
			// If it's been enough time (say, 10 minutes), generate new session ID
			// to help prevent session hijacking
			session_regenerate_id();
			$_SESSION['timestamp'] = time();
		}

		$this->LastActivity = now();
		$this->Save();
	}

	// Returns the FHE group object for this member, if any
	public function FheGroup()
	{
		return FheGroup::Load($this->FheGroup);
	}

	// Returns when this user last logged in
	public function LastActive()
	{
		return $this->LastActivity;
	}

	// Returns when this user was last updated
	public function LastUpdated()
	{
		return $this->LastUpdated;
	}

	// Returns the timestamp this user was last active on the site
	public function LastActivity()
	{
		return $this->LastActivity;
	}

	// Returns the timestamp this user registered
	public function RegistrationDate()
	{
		return $this->RegistrationDate;
	}

	// Returns this member's unique, random (hopefully) salt.
	public function Salt()
	{
		return $this->Salt;
	}

	// If member is over 30 years of age, or is in the bishopric or high council,
	// address them as brother or sister
	public function FirstName()
	{
		if (StakeLeader::IsLoggedIn())
			return $this->FirstName;

		$formal = false;
		$callings = $this->Callings();

		$merited = array("Bishop", "Bishopric 1st Counselor", "Bishopric 2nd Counselor", "High Counselor");

		// First check for calling
		foreach ($callings as $c)
		{
			if ($c->Name == "Bishop")
				return "Bishop";	// Bishop gets own title

			if (in_array($c->Name, $merited))
			{
				$formal = true;
				break;
			}
		}

		// Now check age if not already decided
		if (!$formal)
		{
			$secondsPerYear = 31557600;
			$formal = floor(abs(strtotime(now()) - strtotime($this->Birthday)) / $secondsPerYear) > 30;
		}

		return $formal ? Gender::RenderLDS($this->Gender) : $this->FirstName;
	}

	// Internal use only: sets a new salt and hashed password to this user.
	// Doesn't persist instance in DB, only sets a couple variables.
	private function SetNewPassword($newPwd)
	{
		$this->Salt = salt();
		$this->Password = hashPwd($newPwd, $this->Salt);
	}

	// Entirely deletes: calling assignments, permissions, privileges,
	// survey answers, password reset tokens, and custom residence (if any)
	private function DeleteWardItems()
	{
		// Delete calling assignments
		$q = "DELETE FROM MembersCallings WHERE MemberID='$this->ID'";
		if (!DB::Run($q))
			fail("Tried to delete member ID $this->ID's calling assignments, but failed: ".mysql_error());

		// Delete permissions for this MEMBER (not his/her calling)
		$q = "DELETE FROM Permissions WHERE ObjectType='Member' AND ObjectID='$this->ID'";
		if (!DB::Run($q))
			fail("Deleted calling assignments for this member, but could not delete permissions. MySQL error: ".mysql_error());

		// Delete privileges for this MEMBER (not his/her calling)
		$q = "DELETE FROM GrantedPrivileges WHERE MemberID='$this->ID'";
		if (!DB::Run($q))
			fail("Deleted calling assignments, and permissions for this member, but could not delete granted privileges. MySQL error: ".mysql_error());

		// Delete any password reset tokens
		$q = "DELETE FROM PwdResetTokens WHERE CredentialsID='$this->CredentialsID'";
		if (!DB::Run($q))
			fail("Deleted this member's calling assignments, privileges, and permissions, but not password reset tokens: ".mysql_error());

		// Delete survey answers
		$q = "DELETE FROM SurveyAnswers WHERE MemberID='$this->ID'";
		if (!DB::Run($q))
			fail("Deleted permissions, callings, privileges, and password reset tokens, but not survey answers. Problem was: ".mysql_error());

		// Delete custom Residence, if any
		if ($this->HasCustomResidence())
		{
			$q = "DELETE FROM Residences WHERE ID='$this->ResidenceID' AND Custom=1";
			if (!DB::Run($q))
				fail("Deleted permissions, callings, privileges, password reset tokens, survey answers, and credentials, but not Residence: ".mysql_error());
		}
	}

}
?>