<?php

/**
 * @author Matthew Holt
 */


class SMSJob
{
	private $ID;				// ID of the SMS job
	public $WardID;				// ID of the ward, if sent by a Member
	public $StakeID;			// ID of the Stake, if sent by a StakeLeader
	public $NumbersUsed;		// Numbers used, as provided by our SMS service, to send the message(s) (always a JSON string)
	public $SenderID;			// Member or StakeLeader ID, as dictated by whether $WardID or $StakeID is populated
	public $SenderName;			// The name of the sender, mostly for preservation purposes
	public $SenderPhone;		// The phone number of the sender
	public $Message;			// The body of the text message
	public $SegmentCount;		// How many segments the message was split into for each recipient
	public $Cost;				// The cost of sending all the message segments to all recipients
	public $Recipients = array();	// List of all recipients, their names and numbers, stored as JSON
	public $Started;			// Timestamp this job started
	public $Finished;			// Timestamp this job finished/ended
	public $FailedRecipients = array();	// List of recipients who didn't get the message, with reasons, stored as JSON

	// Returns a populated SMSJob object if the ID is good.
	public static function Load($id)
	{
		$id = DB::Safe($id);
		$q = "SELECT * FROM SMSJobs WHERE ID='$id' LIMIT 1";
		$r = DB::Run($q);
		if (!$r)
			fail(mysql_error());
		$smsjob = mysql_fetch_object($r, 'SMSJob');
		$smsjob->DeserializeFields();
		return $smsjob;
	}

	// Saves this SMSJob in the database
	public function Save()
	{
		$q = DB::BuildSaveQuery($this, get_object_vars($this), false);
		$r = DB::Run($q);
		if (!$this->ID)
			$this->ID = mysql_insert_id();
		return $r ? true : false;
	}

	// Removes this SMS job from the database -- should only be done on rare occasions.
	// We preserve a log of sent texts for troubleshooting and to help combat
	// any potential abuse/security/privacy exploits.
	public function Delete()
	{
		if (!$this->ID)
			return false;

		return DB::Run("DELETE FROM SMSJobs WHERE ID='$this->ID' LIMIT 1");
	}


	// Adds a name/number to the recipients list, returns true if successful.
	public function AddRecipient($memID, $name, $number)
	{
		if (!$memID || !$name || !$number || strlen($number) < 10)
			return false;

		$this->Recipients[] = array("memberID" => $memID, "name" => $name, "number" => $number, "attempts" => 0);

		return true;
	}

	// Adds a name/number to the failed recipients list with a reason
	public function AddFailedRecipient($memID, $name, $number, $statusCode, $reason)
	{
		$memID = $memID ? $memID : 0;
		$name = $name ? $name : "";
		$number = $number ? $number : "";
		$statusCode = $statusCode ? $statusCode : "-";
		$reason = $reason ? $reason : "unknown";

		$this->FailedRecipients[] = array("memberID" => $memID, "name" => $name, "number" => $number, "status_code" => $statusCode, "reason" => $reason);
		return true;
	}

	// If a MEMBER already has a job in the queue (not finished), returns true
	// Specify "$max" if you want to allow more than 1 at a time. 1 is default.
	public static function UnfinishedJobExistsWithMemberID($id, $max = 1)
	{
		$id = DB::Safe($id);
		$r = DB::Run("SELECT ID FROM SMSJobs WHERE SenderID='$id' AND WardID != 0 AND Finished = 0 LIMIT {$max}");
		return mysql_num_rows($r) >= $max;
	}

	// If a stake leader (by $id) already has a job in the queue (not finished), return true
	// Specify "$max" if you want to allow more than 1 at a time. 1 is default.
	public static function UnfinishedJobExistsWithLeaderID($id, $max = 1)
	{
		$id = DB::Safe($id);
		$r = DB::Run("SELECT ID FROM SMSJobs WHERE SenderID='$id' AND StakeID != 0 AND Finished = 0 LIMIT {$max}");
		return mysql_num_rows($r) >= $max;
	}

	// Saves the object and starts the sending job,
	// which runs in the background. No return value.
	// Will not start if already started or ended or
	// if some vital information is missing.
	public function Start()
	{
		// Necessary fields must be basically valid
		if ($this->Started > 0
			|| $this->Finished > 0
			|| (!$this->StakeID && !$this->WardID)
			|| !$this->SenderID
			|| !$this->Message
			|| !$this->Recipients
			|| count($this->Recipients) == 0)
			return false;

		// Populate the sender name and email fields for preservation purposes
		if ($this->IsMemberSender())
		{
			$mem = Member::Load($this->SenderID);
			$this->SenderName = $mem->FirstName()." ".$mem->LastName;
			$this->SenderPhone = $mem->PhoneNumber;
		}
		else
		{
			$leader = StakeLeader::Load($this->SenderID);
			$this->SenderName = $leader->Title." ".$leader->FirstName." ".$leader->LastName;
			$this->SenderPhone = $leader->PhoneNumber;
		}

		// We leave sendsms.php to set and save the "start" timestamp; we don't do it here.
		$this->Save();

		// See EmailJob.php for any explanation about this last part
		$docroot = DOCROOT;
		$smspwd = SMS_JOB_PASSWORD;
		$cmd = "php $docroot/api/sendsms.php $this->ID $smspwd";
		exec("/usr/bin/nohup $cmd &> error_log &");
		return true;
	}

	public function Done()
	{
		return $this->Finished > 0;
	}

	public function IsMemberSender()
	{
		return $this->WardID > 0 && $this->StakeID == 0;
	}

	public function IsLeaderSender()
	{
		return $this->StakeID > 0 && $this->WardID == 0;
	}

	// Transforms Recipients and FailedRecipients into PHP arrays from JSON strings
	private function DeserializeFields()
	{
		if (!is_array($this->Recipients))
			$this->Recipients = json_decode($this->Recipients);
		if (!is_array($this->FailedRecipients))
			$this->FailedRecipients = json_decode($this->FailedRecipients);
	}

	// Returns the ID of this object
	public function ID()
	{
		return $this->ID;
	}
}

?>