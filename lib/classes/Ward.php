<?php

/**
 * @author Matthew Holt
 */


class Ward
{
	private $ID;			// Unique ID. Required.
	public $Name;			// The name of the ward. Required.
	private $StakeID;		// The stake this ward belongs to. Required.
	private $Password;		// The hashed registration password chosen by the ward. Required.
	private $Salt;			// The salt for the ward which is generated to securely hash the password.
	public $Balance;		// The ward's current "balance" (negative means they should reimburse the webmaster) - to 3 decimal places
	public $LastReimbursement;	// The date of the last re-imbursement (their balance was "reset"/is "effective" on this date...)
	public $Deleted;		// If non-falsey, this ward has been deleted and shouldn't be shown

	// Returns a populated Ward object, if the ID is good.
	public static function Load($id)
	{
		$id = DB::Safe($id);
		$q = "SELECT * FROM Wards WHERE ID='$id' LIMIT 1";
		$r = DB::Run($q);
		if (!$r)
			fail(mysql_error());
		$ward = mysql_fetch_object($r, 'Ward');
		return $ward;
	}

	// We have to use this function to create a ward because the constructor
	// is called by the Load function to create the object... this has to do
	// operations which would otherwise break it.
	public static function Create($name, $stakeID, $rawPwd)
	{
		if (!strlen(trim($name)) || !$stakeID || !strlen(trim($rawPwd)))
			fail("Cannot create a ward without a name, stake ID, and password (and residences are strongly recommended, if possible).");
		
		if (!Stake::Load($stakeID))
			fail("Could not create ward because stake ID was found to be invalid.");
		
		$ward = new Ward();
		$ward->Name = strip_tags($name);
		$ward->StakeID = $stakeID;
		$ward->Salt = salt();
		$ward->Password = hashPwd($rawPwd, $ward->Salt);
		$ward->Balance = 2.50;
		$ward->Deleted = false;

		if (!$ward->Save())
			return null;
		
		// Set up pre-defined callings, privileges, permissions, and a sample survey question or two.
		$callings = array();
		$callings[1] = new Calling("Bishop", $ward->ID, true);
		$callings[2] = new Calling("Bishopric 1st Counselor", $ward->ID, true);
		$callings[3] = new Calling("Bishopric 2nd Counselor", $ward->ID, true);
		$callings[4] = new Calling("Executive Secretary", $ward->ID, true);
		$callings[5] = new Calling("Elders Quorum President", $ward->ID, true);
		$callings[6] = new Calling("Elders Quorum 1st Counselor", $ward->ID, true);
		$callings[7] = new Calling("Elders Quorum 2nd Counselor", $ward->ID, true);
		$callings[8] = new Calling("Elders Quorum Secretary", $ward->ID, true);
		$callings[9] = new Calling("Relief Society President", $ward->ID, true);
		$callings[10] = new Calling("Relief Society 1st Counselor", $ward->ID, true);
		$callings[11] = new Calling("Relief Society 2nd Counselor", $ward->ID, true);
		$callings[12] = new Calling("Relief Society Secretary", $ward->ID, true);
		$callings[13] = new Calling("Ward Clerk", $ward->ID, true);
		$callings[14] = new Calling("Membership Clerk", $ward->ID, true);
		
		foreach ($callings as $c)
			$c->Save();	// Save each calling

		// Compile an array of each privilege in the database; currently, we have IDs 1 through 13
		$privileges = array();
		$priv_count = mysql_fetch_row(DB::Run("SELECT COUNT(1) FROM Privileges"))[0];
		for ($i = 1; $i <= $priv_count; $i++)
			$privileges[$i] = Privilege::Load($i);

		// Bishopric (excluding executive secretary) can mass email all ward members,
		// see everything in the export file, and manage privileges, and send texts
		for ($i = 1; $i <= 3; $i++)
		{
			$privileges[PRIV_EMAIL_ALL]->GrantToCalling($callings[$i]->ID());
			$privileges[PRIV_EXPORT_EMAIL]->GrantToCalling($callings[$i]->ID());
			$privileges[PRIV_EXPORT_PHONE]->GrantToCalling($callings[$i]->ID());
			$privileges[PRIV_EXPORT_BDATE]->GrantToCalling($callings[$i]->ID());
			$privileges[PRIV_MNG_SITE_PRIV]->GrantToCalling($callings[$i]->ID());
			$privileges[PRIV_TEXT_ALL]->GrantToCalling($callings[$i]->ID());
		}

		// Executive secretary gets all privileges (except redundant ones 2 and 3 - mass email brothers/sisters)
		for ($i = PRIV_EMAIL_ALL; $i <= PRIV_TEXT_ALL; $i ++)
			if ($i != PRIV_EMAIL_BRO && $i != PRIV_EMAIL_SIS)
				$privileges[$i]->GrantToCalling($callings[4]->ID());

		// EQ presidency gets to mass-email all brothers
		for ($i = 5; $i <= 8; $i++)
			$privileges[PRIV_EMAIL_BRO]->GrantToCalling($callings[$i]->ID());

		// The EQ president needs to see more in the export file
		$privileges[PRIV_EXPORT_EMAIL]->GrantToCalling($callings[5]->ID());
		$privileges[PRIV_EXPORT_PHONE]->GrantToCalling($callings[5]->ID());
		$privileges[PRIV_EXPORT_BDATE]->GrantToCalling($callings[5]->ID());

		
		// RS presidency gets to mass-email all sisters
		for ($i = 9; $i <= 12; $i++)
			$privileges[PRIV_EMAIL_SIS]->GrantToCalling($callings[$i]->ID());

		// RS president can see more in the export file, too
		$privileges[PRIV_EXPORT_EMAIL]->GrantToCalling($callings[9]->ID());
		$privileges[PRIV_EXPORT_PHONE]->GrantToCalling($callings[9]->ID());
		$privileges[PRIV_EXPORT_BDATE]->GrantToCalling($callings[9]->ID());

		// Ward clerks can see all info in export file and manage site privileges
		$privileges[PRIV_EXPORT_EMAIL]->GrantToCalling($callings[13]->ID());
		$privileges[PRIV_EXPORT_PHONE]->GrantToCalling($callings[13]->ID());
		$privileges[PRIV_EXPORT_BDATE]->GrantToCalling($callings[13]->ID());
		$privileges[PRIV_MNG_SITE_PRIV]->GrantToCalling($callings[13]->ID());

		// Membership clerks needs to see all info in export file, and can
		// manage callings, profile pictures, and delete accounts
		$privileges[PRIV_EXPORT_EMAIL]->GrantToCalling($callings[14]->ID());
		$privileges[PRIV_EXPORT_PHONE]->GrantToCalling($callings[14]->ID());
		$privileges[PRIV_EXPORT_BDATE]->GrantToCalling($callings[14]->ID());
		$privileges[PRIV_MNG_CALLINGS]->GrantToCalling($callings[14]->ID());
		$privileges[PRIV_MNG_PROFILE_PICS]->GrantToCalling($callings[14]->ID());
		$privileges[PRIV_DELETE_ACCTS]->GrantToCalling($callings[14]->ID());


		// --------------------------------------------------- //


		// Create a sample/starter question.
		$qu = new SurveyQuestion();
		$qu->Question = "Welcome to the singles ward! Do you prefer blue, brown, or green eyes?";
		$qu->QuestionType = QuestionType::MultipleChoice;
		$qu->Required = false;
		$qu->Visible = true;
		$qu->WardID = $ward->ID();
		$qu->Save();
		$qu->AddAnswerOption("Brown eyes");
		$qu->AddAnswerOption("Blue eyes");
		$qu->AddAnswerOption("Green eyes");

		// Let a few people see it: Bishop, Exec. Sec, EQP, and RSP
		$p = new Permission();
		$p->QuestionID($qu->ID());
		$p->Allow($callings[1]->ID(), "Calling", true);
		$p->Allow($callings[4]->ID(), "Calling", true);
		$p->Allow($callings[5]->ID(), "Calling", true);
		$p->Allow($callings[9]->ID(), "Calling", true);

		// I think we're all done here!
		return $ward;
	}


	// Saves this ward in the database.
	public function Save()
	{
		if (!$this->Name || !$this->StakeID || !$this->Password)
			return false;

		if (!$this->ID)
			$this->ID = 0;
		
		$this->Name = trim(strip_tags(str_ireplace("ward", "", $this->Name)));

		// Sanitize the name before we use it in our query below... (and strip tags)
		$safeName = DB::Safe($this->Name);

		// Make sure the ward name is unique
		$q = "SELECT 1 FROM Wards WHERE Name='$safeName' AND StakeID='$this->StakeID' AND ID!='$this->ID' LIMIT 1";
		if (mysql_num_rows(DB::Run($q)) > 0)
			fail("Oops. Could not save Ward information; the name of the ward already exists in its stake.");

		$q = DB::BuildSaveQuery($this, get_object_vars($this));
		$r = DB::Run($q);
		if (!$this->ID)
			$this->ID = mysql_insert_id();
		return $r ? true : false;
	}

	/* 	
		Deletes a ward and everything in it -- including members, survey,
		FHE groups, etc. (Leaves any SMS or Email jobs.)

		Deletes a ward from the database. Set $hardDelete to delete the Ward
		table entry as well instead of keeping it in the Wards table with the
		Deleted flag set to to true...
		
		$sure must === true (not 1, "yes", etc) to succeed. (safety switch)
	*/
	public function Delete($sure = false, $hardDelete = false)
	{

		if ($sure !== true)
			fail("Cannot delete ward; please pass boolean true as a second argument.");

		if (!$this->ID)
			return false;

		$wid = $this->ID;	// convenience

		// FHE groups
		DB::Run("DELETE FROM FheGroups WHERE WardID=$wid");


		// Residences
		$res = $ward->Residences(true);
		foreach ($res as $residence)
			$res->Delete(true);


		// SurveyQuestions, SurveyAnswers, SurveyAnswerOptions, Permissions
		$r = DB::Run("SELECT ID FROM SurveyQuestions WHERE WardID=$wid");
		while ($row = mysql_fetch_array($r))
		{
			$sq = SurveyQuestion::Load($row['ID']);
			$sq->Delete(true);
		}

		// Callings, MembersCallings, and any remaining calling Permissions (shouldn't be any...)
		$r = DB::Run("SELECT ID FROM Callings WHERE WardID=$wid");
		while ($row = mysql_fetch_array($r))
		{
			$c = Calling::Load($row['ID']);
			$c->Delete(true);
		}


		// Members, Credentials, GrantedPrivileges, remaining Callings, PwdResetTokens,
		// profile pic, and remaining member Permissions (shouldn't be any...)
		// (Everything else except the ward itself)
		$r = DB::Run("SELECT ID FROM Members WHERE WardID=$wid");
		while ($row = mysql_fetch_array($r))
		{
			$m = Member::Load($row['ID']);
			$m->Delete(true);
		}

		// Ward itself
		if ($hardDelete)
		{
			DB::Run("DELETE FROM Wards WHERE ID=$wid LIMIT 1");

			// Unset this object so it can't inadvertently be saved again
			$this->ID = null;
			$this->Name = null;
		}
		else
		{
			$this->Deleted = true;
			$this->Save();
		}

		return true;
	}

	// Returns the Member object for the bishop of this ward.
	// If no bishop, returns null.
	public function GetBishop()
	{	
		$q = DB::Run("SELECT `Members`.`ID`
						FROM
							`MembersCallings`
						INNER JOIN
							`Callings`
						ON
							`MembersCallings`.`CallingID` = `Callings`.`ID`
						INNER JOIN
							`Members`
						ON
							`MembersCallings`.`MemberID` = `Members`.`ID`
						WHERE
							`Callings`.`WardID`={$this->ID} AND
							`Callings`.`Name`='Bishop' AND
							`Callings`.`Preset`=1
						LIMIT 1;");

		if (!mysql_num_rows($q))
			return null;
		else
		{
			$r = mysql_fetch_array($q);
			return Member::Load($r['ID']);
		}
	}

	// Returns the ID of this object
	public function ID()
	{
		return $this->ID;
	}

	// Returns the stake ID of this object.
	public function StakeID()
	{
		return $this->StakeID;
	}

	// Returns the hashed registration password for this ward.
	public function Password()
	{
		return $this->Password;
	}

	// Returns the salt for this ward's registration password.
	public function Salt()
	{
		return $this->Salt;
	}

	// Sets the new password for this ward. Pass in 'true' in the second parameter to save.
	public function SetPassword($newPwd, $save = false)
	{
		$this->Salt = salt();
		$this->Password = hashPwd($newPwd, $this->Salt);

		if ($save)
			$this->Save();
	}

	// Returns true if the string given in "$input" matches the
	// password stored in the database. False otherwise.
	public function PasswordMatches($input)
	{
		return hashPwd($input, $this->Salt) == $this->Password;
	}

	// Returns an array of residential areas in this ward (Residence objects)
	// Pass in 'true' to include the custom/non-standard ones, too.
	public function Residences($includeCustom = false)
	{
		$res = array();
		$query = "SELECT ID FROM Residences WHERE WardID='{$this->ID()}' ";
		if (!$includeCustom)
			$query .= "AND Custom=0";
		$query .= " ORDER BY Name ASC";
		$q = DB::Run($query);
		while ($row = mysql_fetch_array($q))
			$res[] = Residence::Load($row['ID']);
		return $res;
	}

	// Adds a Residence belonging to this ward to the database. Returns the Residence.
	public function AddResidence($name, $street, $city, $state, $zip, $custom = false)
	{
		$res = new Residence();
		$res->WardID($this->ID);
		$res->Name = $name;
		$res->Address = $street;
		$res->City = $city;
		$res->State = $state;
		$res->PostalCode = $zip;
		$res->Custom($custom);
		$res->Save();
		return $res;
	}
}

?>