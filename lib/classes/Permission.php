<?php

/**
 * @author Matthew Holt
 */


class Permission
{	
	private $ID;
	private $QuestionID;	// ID of the related question
	private $ObjectID;		// ID of the calling or member
	private $ObjectType;	// "Calling" or "Member" string
	
	// Returns a populated Permission object if the ID is good.
	public static function Load($id)
	{
		$q = "SELECT * FROM Permissions WHERE ID='$id' LIMIT 1";
		$r = DB::Run($q);
		if (!$r)
			die(mysql_error());
		$permission = mysql_fetch_object($r, 'Permission');
		return $permission;
	}
	
	public function Save()
	{
		if (!$this->QuestionID || !$this->ObjectID || !$this->ObjectType)
			return false;
		
		// Make sure the permission is unique
		$q = "SELECT 1 FROM Permissions WHERE QuestionID='$this->QuestionID' AND ObjectID='$this->ObjectID' AND ObjectType='$this->ObjectType' LIMIT 1";
		if (mysql_num_rows(DB::Run($q)) > 0)
			// We don't need to kill the script...? Just return false.
			//die("It appears that ".strtolower($this->ObjectType)." already has permissions for that information. Permission not saved; aborting.");
			return false;
		
		$q = DB::BuildSaveQuery($this, get_object_vars($this));
		$r = DB::Run($q);
		if (!$this->ID)
			$this->ID = mysql_insert_id();
		return $r ? true : false;
	}
	
	
	/**
	 * Associate a member or calling with this permission.
	 * @var $objID The ID of the Calling or Member. 0 is wildcard.
	 * @var $type "Calling" or "Member" only.
	 * @var $all If explicitly === true and $objID == 0, it turns off
	 * 	the safety and this permission becomes a wildcard. EVERY
	 *	member or EVERY calling will have access via this permission. Careful!
	 *	-- Not used... decided it wasn't a good idea... --
	 * @return True if successful, false otherwise.
	 * 
	 * *** MUST SET QuestionID FIRST.
	 * *** Auto-saves.
	*/
	public function Allow($objID, $type)
	{
		if (!$this->QuestionID)
			return false;			// Safety
		
		if (!$objID)
			return false;			// Safety
		
		$type = trim(ucfirst($type));	// Standardize
		if ($type != "Member" && $type != "Calling")
			return false;			// Safety
		
		// Does the object (calling/member) exist?
		$obj = null;
		if ($type == "Member")
			$obj = Member::Load($objID);
		else
			$obj = Calling::Load($objID);
		
		if (!$obj)
			die("ERROR > That member or calling does not exist. Try again...");

		$this->ObjectID = $objID;
		$this->ObjectType = $type;
		return $this->Save();
	}
	
	// Deletes this permission entry. Pass boolean true to succeed,
	// just to be sure.
	public function Delete($sure = false)
	{
		if ($sure !== true)
			fail("Could not delete this permission. Please pass boolean true as an argument.");
		
		if (!$this->ID)
			return false;
		
		$q = "DELETE FROM Permissions WHERE ID='$this->ID' LIMIT 1";
		
		if (!DB::Run($q))
			fail("Could not delete permission, please report this: ".mysql_error());
		
		// De-construct this object
		$this->ID = null;
		$this->QuestionID = null;
		$this->ObjectID = null;
		$this->Object = null;
		
		return true;
	}
	
	
	// If not already set, sets the question ID for this permission
	// Returns the Question ID
	public function QuestionID($id = 0)
	{
		// The Question ID won't change; the permission must be deleted
		// then re-created by that question.
		if (!$this->QuestionID && $id > 0)
			$this->QuestionID = $id;
		return $this->QuestionID;
	}
	
	// Returns the ID of this object
	public function ID()
	{
		return $this->ID;
	}
	
	// Returns the ID of the object that has permission
	public function ObjectID()
	{
		return $this->ObjectID;
	}
	
	// Returns the type of the object that has permission
	public function ObjectType()
	{
		return $this->ObjectType;
	}

	// Returns true if this permission is in the same ward as
	// the given ward ID, false otherwise. The permission's ward
	// is determined by the object's ward it is associated with.
	public function InWard($wardID)
	{
		$obj = $this->Object();
		if ($this->ObjectType == 'Calling')
			return $obj->WardID() == $wardID;
		else if ($this->ObjectType == 'Member')
			return $obj->WardID == $wardID;
		else
			return false;
	}
	
	// Returns the associated Calling or Member for this permission,
	// or null if no matches.
	public function Object()
	{
		if (!$this->ObjectID)
			return null;
		
		if ($this->ObjectType == 'Calling')
			return Calling::Load($this->ObjectID);
		elseif ($this->ObjectType == 'Member')
			return Member::Load($this->ObjectID);
		else
			return null;
	}
}

?>