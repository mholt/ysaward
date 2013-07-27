<?php

/**
 * @author Matthew Holt
 */


class Privilege
{
	private $ID;
	private $Privilege;
	private $HelpText;

	// Returns a populated Privilege object if the ID is good.
	public static function Load($id)
	{
		$q = "SELECT * FROM Privileges WHERE ID='$id' LIMIT 1";
		$r = DB::Run($q);
		if (!$r)
			die(mysql_error());
		$priv = mysql_fetch_object($r, 'Privilege');
		return $priv;
	}


	// Grant this privilege to a member with a certain ID
	public function GrantToMember($memberID)
	{
		return $this->Grant($memberID, "Member");
	}

	// Grant this privilege to a calling with a certain ID
	public function GrantToCalling($callingID)
	{
		return $this->Grant($callingID, "Calling");
	}

	// Revoke this privilege from a member with a certain ID
	public function RevokeFromMember($memberID)
	{
		return $this->Revoke($memberID, "Member");
	}

	// Revoke this privilege from a calling with a certain ID
	public function RevokeFromCalling($callingID)
	{
		return $this->Revoke($callingID, "Calling");
	}


	private function Grant($objectID, $objectType)
	{
		if ($objectType != "Member" && $objectType != "Calling" || !$objectID || !$this->ID)
			return false;

		$r1 = DB::Run("SELECT * FROM GrantedPrivileges WHERE {$objectType}ID='$objectID' AND PrivilegeID='$this->ID' LIMIT 1");
		if (mysql_num_rows($r1) > 0)
			return true;

		$q = "INSERT INTO GrantedPrivileges (PrivilegeID, {$objectType}ID) VALUES($this->ID, $objectID)";
		$r = DB::Run($q);

		if (!$r)
			die("ERROR > Could not grant privilege... " . mysql_error());

		return true;
	}


	private function Revoke($objectID, $objectType)
	{
		if ($objectType != "Member" && $objectType != "Calling" || !$objectID || !$this->ID)
			return false;

		$q = "DELETE FROM GrantedPrivileges WHERE PrivilegeID='$this->ID' AND {$objectType}ID='$objectID' LIMIT 1";

		if (!DB::Run($q))
			die("ERROR > Could not revoke that privilege... sorry! " . mysql_error());

		return true;
	}


	public function ID()
	{
		return $this->ID;
	}

	public function Privilege()
	{
		return $this->Privilege;
	}

	public function HelpText()
	{
		return $this->HelpText;
	}
}

?>