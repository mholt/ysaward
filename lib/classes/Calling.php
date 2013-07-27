<?php

/**
 * @author Matthew Holt
 */


class Calling
{
	private $ID;			// Unique ID
	public $Name;			// The name of the calling. REQUIRED.
	private $Preset;		// Whether this calling comes with every ward
	private $WardID;		// The ID of the ward this calling belongs to

	// Creates and saves a calling with $name in the database for ward $wardID.
	// If this is created automatically, or comes by default with a ward,
	// set $preset to true.
	public function __construct($name = null, $wardID = 0, $preset = false)
	{
		if (!$name || strlen(trim($name)) == 0 || !$wardID)
			return;

		if (!Ward::Load($wardID))
			fail("Could not create calling because the ward ID passed in is invalid.");

		$this->Name = strip_tags($name);
		$this->Preset = $preset;
		$this->WardID = $wardID;
		$this->Save();
	}

	// Returns a populated Calling object, if the ID is good.
	public static function Load($id)
	{
		$id = DB::Safe($id);
		$q = "SELECT * FROM Callings WHERE ID='$id' LIMIT 1";
		$r = DB::Run($q);
		if (!$r)
			fail(mysql_error());
		$calling = mysql_fetch_object($r, 'Calling');
		return $calling;
	}

	// Saves this calling in the database.
	public function Save()
	{
		if (!$this->Name || !$this->WardID)
			return false;

		if (!$this->ID)
			$this->ID = 0;
		
		$this->Name = trim($this->Name);

		// Sanitize the name before we use it in our query below...
		$safeName = DB::Safe($this->Name);

		// Make sure the calling title is unique
		$q = "SELECT 1 FROM Callings WHERE Name='$safeName' AND WardID='$this->WardID' AND ID!='$this->ID' LIMIT 1";
		if (mysql_num_rows(DB::Run($q)) > 0)
			fail("Oops. Could not save Calling information; the name of the calling already exists in this ward.");

		$q = DB::BuildSaveQuery($this, get_object_vars($this));
		$r = DB::Run($q);
		if (!$this->ID)
			$this->ID = mysql_insert_id();
		return $r ? true : false;
	}

	/* 	Deletes a calling and all associated assignments to members
		and also all permissions associated with this calling.
		$sure must === true (not 1, "yes", etc) to succeed. (safety switch)
	*/
	public function Delete($sure = false)
	{
		if ($sure !== true)
			fail("Cannot delete calling; please pass boolean true as an argument.");

		if (!$this->ID || !$this->Name)
			return false;

		// Delete all permissions for this calling
		$q = "DELETE FROM Permissions WHERE ObjectID='$this->ID' AND ObjectType='Calling'";
		if (!DB::Run($q))
			fail("Could not delete permissions for this calling. Nothing has been touched; please report this: ".mysql_error());

		// Delete all assignments to members of this calling
		$q = "DELETE FROM MembersCallings WHERE CallingID='$this->ID'";
		if (!DB::Run($q))
			fail("Could not delete permissions for this calling. Permissions for this calling were already deleted; please report this: ".mysql_error());

		// Delete this calling.
		$q = "DELETE FROM Callings WHERE ID='$this->ID' LIMIT 1";
		if (!DB::Run($q))
			fail("Could not delete permissions for this calling. Permissions and assignements have already been deleted; please report this: ".mysql_error());

		// Unset this object so it can't inadvertently be saved again
		$this->ID = null;
		$this->Name = null;

		return true;
	}

	// Returns whether or not this calling
	// has a privilege associated to it (given by an ID)
	public function HasPrivilege($privID)
	{
		$q = "SELECT ID FROM GrantedPrivileges WHERE CallingID={$this->ID} AND PrivilegeID={$privID} LIMIT 1";
		$r = DB::Run($q);
		return mysql_num_rows($r) > 0;
	}

	// Returns a list of members (Member objects) with this calling assigned
	public function Members()
	{
		$q = "SELECT `MemberID` FROM `MembersCallings` WHERE `CallingID`={$this->ID}";
		$r = DB::Run($q);
		$members = array();
		while ($row = mysql_fetch_array($r))
			$members[] = Member::Load($row['MemberID']);
		return $members;
	}

	// Returns the ID of this object
	public function ID()
	{
		return $this->ID;
	}

	// Returns the ID of the ward of this object
	public function WardID()
	{
		return $this->WardID;
	}

	// Returns whether this calling is preset (pretty much every ward has one; cannot delete it)
	public function Preset()
	{
		return $this->Preset;
	}
}

?>