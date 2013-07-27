<?php

/**
 * @author Matthew Holt
 */

class StakeLeader
{
	private $ID;
	private $CredentialsID;
	public $StakeID;
	public $Gender;
	public $Calling;
	public $Title;
	public $FirstName;
	public $LastName;
	public $ViewGender;
	public $LastActivity;
	public $RegistrationDate;
	public $Email;
	public $PhoneNumber;
	private $Password;
	private $Salt;


	public function __construct()
	{
		if (!$this->ID) // new stake leaders only
			$this->RegistrationDate = now();
	}

	// Loads a stake leader, instantiates it, and returns it.
	public static function Load($id)
	{
		$q = "SELECT StakeLeaders.*, Credentials.Email, Credentials.Password, Credentials.Salt FROM StakeLeaders INNER JOIN Credentials ON StakeLeaders.ID = Credentials.StakeLeaderID WHERE StakeLeaders.ID='$id' LIMIT 1";
		$r = DB::Run($q);
		if (!$r)
			fail(mysql_error());
		$m = mysql_fetch_object($r, 'StakeLeader');
		return $m;
	}

	// Loads the currently logged-in stake leader object
	public static function Current()
	{
		if (!StakeLeader::IsLoggedIn())
			return null;
		
		return StakeLeader::Load($_SESSION['stakeLeaderID']);
	}

	/**
	 * Logs this stake leader into the session given username and password
	 * @var $eml = The email address
	 * @var $pwd = The plaintext password (will be salted and hashed)
	 * @return If successful, the StakeLeader object. Otherwise null.
	*/
	public static function Login($eml, $pwd)
	{
		// Sanitize input
		$eml = DB::Safe($eml);

		// First, we need to obtain this stake leader's unique salt
		$r = DB::Run("SELECT `Salt` FROM `Credentials` WHERE `Email`='$eml' AND `StakeLeaderID` > 0 LIMIT 1");
		if (mysql_num_rows($r) == 0)
			return null;

		$salt = mysql_result($r, 0);

		// Now hash input according to our hashing algorithm and leader's salt
		$pwd = hashPwd($pwd, $salt);

		// See if the email/password combination are correct
		$try = DB::Run("SELECT StakeLeaderID FROM Credentials WHERE Email='$eml' AND Password='$pwd' AND StakeLeaderID > 0 LIMIT 1");
		if (mysql_num_rows($try) == 0)
			return null;

		// At this point, valid credentials were entered. Proceed...
		$stakeLeaderID = mysql_result($try, 0);
		$stakeLeader = StakeLeader::Load($stakeLeaderID);

		// Update LastActivity
		$stakeLeader->UpdateLastActivity();

		// Since they've logged in, no more need for existing
		// password reset tokens. Delete any strays for security.
		$q = "DELETE FROM PwdResetTokens WHERE CredentialsID={$stakeLeader->CredentialsID()}";
		DB::Run($q);

		// Save the session. This is the actual "logging in" part.
		session_regenerate_id();			// Helps prevent session hijacking
		$_SESSION["stakeLeaderID"] = $stakeLeaderID;
		$_SESSION["timestamp"] = time();
		$_SESSION["ipaddress"] = $_SERVER['REMOTE_ADDR'];

		return $stakeLeader;
	}

	// Logs out the current stake leader (and user, for that matter). The logout functions are the same
	public static function Logout()
	{
		$_SESSION = array();
		session_destroy();
		session_start();
		session_regenerate_id();
		return !isset($_SESSION['stakeLeaderID']);
	}

	// Returns true or false if the stake leader is logged in or not
	public static function IsLoggedIn()
	{
		// To prevent possible session hijacking, compare IP addresses
		// from what they logged in with to what the current client has.
		// If it's different, the session ID was probably intercepted.
		// In that case, do a full, deliberate logout.
		if (isset($_SESSION['ipaddress']) && $_SESSION['ipaddress'] != $_SERVER['REMOTE_ADDR'])
			StakeLeader::Logout();

		return isset($_SESSION['stakeLeaderID'])
			&& isset($_SESSION['ipaddress'])
			&& isset($_SESSION['timestamp'])
			&& $_SESSION['stakeLeaderID'] > 0
			&& $_SESSION['ipaddress'] == $_SERVER['REMOTE_ADDR'];
	}


	// Create or update this instance. Returns true or false.
	// If false is supplied, the LastUpdated value won't change (default)
	// If true is supplied, the LastUpdated timestamp WILL change.
	public function Save($updateLastUpdated = false)
	{
		// A valid stake ID is required.
		if (!Ward::Load($this->StakeID))
			fail("Cannot save account information for leader with email: $this->Email -- a valid stake ID is required ($this->StakeID is not valid).");

		// Make sure the email address is unique
		$this->Email = trim($this->Email);
		$q = "SELECT 1 FROM Credentials WHERE Email='$this->Email' AND ID!='{$this->CredentialsID}' LIMIT 1";
		if (mysql_num_rows(DB::Run($q)) > 0)
			fail("Could not save account info for $this->Email. That email address is already in use by another stake leader or member.");

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
		// in the StakeLeaders table, after using them in building the query.
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


	// Gets an array of Permission objects just as the Member::Permissions() function does.
	// For StakeLeaders, this basically means all survey questions. It's kind of an ad-hoc
	// array which basically functions the same as a genuine permissions lookup.
	// $removeOverlap is here so it interfaces the same as the Member class function,
	// but this function as it stands now, by its nature, will not generate overlap.
	// TODO: We don't use the "ViewGender" field in the database table (yet). The intent is to
	// show only brothers/sisters information if the stake leader isn't in the stake presidency
	// but is rather, say, in the stake relief society presidency...
	public function Permissions($removeOverlap = true)
	{
		$permissions = array();
		$wardID = DB::Safe($_SESSION['wardID']);
		$questionQuery = DB::Run("SELECT ID FROM SurveyQuestions WHERE WardID='{$wardID}'");

		while ($questionRow = mysql_fetch_array($questionQuery))
		{
			$per = new Permission();	// Ad-hoc part. We're not looking up permissions by calling or name.
			$per->QuestionID($questionRow['ID']);
			$permissions[] = $per;
		}

		return $permissions;
	}


	// Changes password. Requires new plaintext password.
	// If resetting the password, you only need to pass in
	// the first argument, but for a deliberate change, both
	// are needed.
	// SAVES THE STAKELEADER OBJECT and returns the result of the save.
	public function ChangePassword($newPwd, $oldPwd = null)
	{
		if (!$newPwd)
			return false;

		// If no oldPwd is passed in, make sure there is permission to
		// simply CHANGE the password (a valid reset token)... then do it.
		if (!isset($oldPwd))
		{
			$q = "SELECT 1 FROM PwdResetTokens WHERE CredentialsID='{$this->CredentialsID}' LIMIT 1";
			if (mysql_num_rows(DB::Run($q)) == 0)
				fail("Can't reset password without old password; no password reset token has been set for this user. To set one, use the Forgot Password link.");

			$this->SetNewPassword($newPwd);

			// Delete the reset token...
			$q = "DELETE FROM PwdResetTokens WHERE CredentialsID='{$this->CredentialsID}'";
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

	// Deletes a stake leader and all info associated with it.
	// $sure must be set to boolean true to be safe.
	// Returns true if successful; false or dies otherwise.
	public function Delete($sure = false)
	{
		// Safety
		if ($sure !== true || !$this->ID)
			return false;

		// Delete any password reset tokens
		$q = "DELETE FROM PwdResetTokens WHERE CredentialsID='$this->CredentialsID'";
		if (!DB::Run($q))
			fail("Could not delete password reset tokens: ".mysql_error());

		// Delete credentials
		$q = "DELETE FROM Credentials WHERE ID='$this->CredentialsID'";
		if (!DB::Run($q))
			fail("Deleted password reset tokens but not anything else (stake leader can still login): ".mysql_error());

		// Delete stake leader record
		$q = "DELETE FROM StakeLeaders WHERE ID='$this->ID' LIMIT 1";
		if (!DB::Run($q))
			fail("Deleted password reset tokens and credentials but not account (stake leader CANNOT login, but record still exists!), problem - ".mysql_error());

		return true;
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

	// Gets the ID of this stake leader
	public function ID()
	{
		return $this->ID;
	}

	// Gets the Credentials ID of the stake leader
	public function CredentialsID()
	{
		return $this->CredentialsID;
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

	// Returns this stake leader's unique, random salt.
	public function Salt()
	{
		return $this->Salt;
	}

	// Internal use only: sets a new salt and
	// hashed password to this user. Doesn't
	// persist instance in DB, only sets a couple variables.
	private function SetNewPassword($newPwd)
	{
		$this->Salt = salt();
		$this->Password = hashPwd($newPwd, $this->Salt);
	}
}
?>