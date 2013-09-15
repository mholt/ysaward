<?php

/**
 * @author Matthew Holt
 */

class FheGroup
{
	private $ID;			// Unique ID. Required.
	public $WardID;			// The ID of the ward. Required.
	public $GroupName;		// Human-readable group name. Required.
	public $Leader1;		// User IDs of the leaders of this group
	public $Leader2;
	public $Leader3;

	// Loads a FHE group, instantiates it, and returns it.
	public static function Load($id)
	{
		$id = DB::Safe($id);
		$q = "SELECT * FROM FheGroups WHERE ID='$id' LIMIT 1";
		$r = DB::Run($q);
		if (!$r)
			fail(mysql_error());
		$m = mysql_fetch_object($r, 'FheGroup');
		return $m;
	}

	// Saves this FHE group in the database.
	public function Save()
	{
		if (!$this->GroupName || !$this->WardID)
			return false;

		if (!$this->ID)
			$this->ID = 0;

		// Pascal-case the FHE group name for consistency
		$this->GroupName = ucwords(strtolower(trim($this->GroupName)));

		// Sanitize the name before we use it in our query below...
		$safeName = DB::Safe($this->GroupName);

		// Make sure the group title is unique
		$q = "SELECT 1 FROM FheGroups WHERE GroupName='$safeName' AND ID!='$this->ID' LIMIT 1";
		if (mysql_num_rows(DB::Run($q)) > 0)
			fail("Oops. Could not save the FHE group; the name is already the name of another group, and they must be unique.");

		$q = DB::BuildSaveQuery($this, get_object_vars($this));
		$r = DB::Run($q);
		if (!$this->ID)
			$this->ID = mysql_insert_id();
		return $r ? true : false;
	}

	// Organizes leadership, e.g. if leader1 and leader3 are assigned, leader2 will fill the gap.
	// Saves the object.
	public function ConsolidateLeaders()
	{
		// Fill leader 1
		if (!$this->Leader1 && $this->Leader2)
		{
			$this->Leader1 = $this->Leader2;
			$this->Leader2 = $this->Leader3;
			$this->Leader3 = 0;
		}

		// Still fill leader 1
		if (!$this->Leader1 && !$this->Leader2 && $this->Leader3)
		{
			$this->Leader1 = $this->Leader3;
			$this->Leader3 = 0;
		}

		// Fill leader 2
		if (!$this->Leader2 && $this->Leader3)
		{
			$this->Leader2 = $this->Leader3;
			$this->Leader3 = 0;
		}

		return $this->Save();
	}

	public function ID()
	{
		return $this->ID;
	}

}
?>