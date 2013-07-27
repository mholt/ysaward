<?php

/**
 * @author Matthew Holt
 */


class Residence
{
	private $ID;
	private $WardID;
	public $Name;
	public $Address;
	public $City;
	public $State;
	public $PostalCode;
	private $Custom;

	// Returns a populated Residence object if the ID is good.
	public static function Load($id)
	{
		$q = "SELECT * FROM Residences WHERE ID='$id' LIMIT 1";
		$r = DB::Run($q);
		if (!$r)
			die(mysql_error());
		$priv = mysql_fetch_object($r, 'Residence');
		return $priv;
	}

	// Saves the Residence object in the database
	public function Save()
	{
		if (!$this->WardID)
			return false;	// Must belong to a ward
		$q = DB::BuildSaveQuery($this, get_object_vars($this));
		$r = DB::Run($q);
		if (!$this->ID)
			$this->ID = mysql_insert_id();
		return $r ? true : false;
	}

	// Deletes this Residence (changes any ResidenceID and Apartment values in the
	// Members table to NULL unless you pass true for the second argument).
	public function Delete($sure = false, $keepResIDs = false)
	{
		if ($sure !== true)
			fail("Cannot delete residence; ensure parameter is boolean true.");

		if (!$keepResIDs)											// The "AND WardID=..." part is unnecessary but just a precaution
			$r = DB::Run("UPDATE Members SET ResidenceID=NULL, Apartment='' WHERE ResidenceID='$this->ID' AND WardID='$this->WardID'");

		$r = DB::Run("DELETE FROM Residences WHERE ID='$this->ID' LIMIT 1");
		return $r ? true : false;
	}


	public function ID()
	{
		return $this->ID;
	}


	// Returns how many residences in the ward have the same name.
	// (e.g. if there are two "Cambridge" complexes, or they are the
	// same complex but have a different street address, we'll need to know that)
	public function Count()
	{
		$name = DB::Safe($this->Name);
		$q = DB::Run("SELECT 1 FROM Residences WHERE Name='{$name}' AND WardID='{$this->WardID}'");
		return mysql_num_rows($q);
	}

	// Gets (if nothing passed in) the ward ID. Otherwise, sets it.
	public function WardID($id)
	{
		if (!$id)
			return $this->WardID;
		else
			$this->WardID = $id;
	}

	// Returns whether or not this Residence entry is custom, or sets the value to true or false
	public function Custom($c = 1)
	{
		if ($c === true || $c === false)
			$this->Custom = $c;
		return $this->Custom;
	}


	// Returns the full address of this residence as a string
	public function String($lineBreaks = false, $simple = false)
	{
		$delim = $lineBreaks ? "\r\n" : " ";
		return ($this->Custom ? "" : $this->Name.$delim).$this->Address.$delim
				.$this->City.$delim.$this->State.($simple ? '' : $delim.$this->PostalCode);
	}


	// Returns true if the residence name passed in is unique this ward,
	// or false if it exists elsewhere.
	public function NameUnique()
	{
		$name = DB::Safe($this->Name);
		$q = DB::Run("SELECT 1 FROM Residences WHERE Name='{$name}' AND WardID='{$this->WardID}' AND ID!='$this->ID' LIMIT 1");
		return mysql_num_rows($q) == 0;
	}
}

?>