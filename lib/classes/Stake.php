<?php

/**
 * @author Matthew Holt
 */


class Stake
{
	private $ID;			// Unique ID. Required.
	public $Name;			// The name of the stake. Required.


	public function __construct($name = null)
	{
		if (!$name || strlen(trim($name)) == 0)
			return;
				
		$this->Name = strip_tags($name);
		$this->Save();
	}

	// Returns a populated Stake object, if the ID is good.
	public static function Load($id)
	{
		$q = "SELECT * FROM Stakes WHERE ID='$id' LIMIT 1";
		$r = DB::Run($q);
		if (!$r)
			fail(mysql_error());
		$stake = mysql_fetch_object($r, 'Stake');
		return $stake;
	}

	// Saves this stake in the database.
	public function Save()
	{
		if (!$this->Name)
			return false;

		if (!$this->ID)
			$this->ID = 0;
		
		$this->Name = str_ireplace("stake", "", $this->Name);
		$this->Name = trim(strip_tags($this->Name));

		// Sanitize the name before we use it in our query below...
		$safeName = DB::Safe($this->Name);

		// Make sure the calling title is unique
		$q = "SELECT 1 FROM Stakes WHERE Name='$safeName' AND ID!='$this->ID' LIMIT 1";
		if (mysql_num_rows(DB::Run($q)) > 0)
			fail("Oops. Could not save Stake information; the name of the stake already exists.");

		$q = DB::BuildSaveQuery($this, get_object_vars($this));
		$r = DB::Run($q);
		if (!$this->ID)
			$this->ID = mysql_insert_id();
		return $r ? true : false;
	}

	/*	TODO.
		Deletes this stake, and if the first parameter is boolean true,
		deletes all dependencies of it (wards, members, callings, etc, etc.)
		Otherwise, wards go into a dissociated state and become inaccessable
		until re-assigned.
		$sure must === true (not 1, "yes", etc) to succeed. (safety switch)
	*/
	public function Delete($comprehensive = false, $sure = false)
	{
		return false;	// TODO. Temporary switch.
		
		if ($sure !== true)
			fail("ERROR > Cannot delete stake; please pass boolean true as the second argument.");

		// TODO.

		// Unset this object so it can't inadvertently be saved again
		$this->ID = null;
		$this->Name = null;

		return false;	// TODO: When finished, return true on success.
	}

	// Returns the ID of this object
	public function ID()
	{
		return $this->ID;
	}
}

?>