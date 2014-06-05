<?php

/**
 * @author Matthew Holt
 */


class EmailJob
{
	private $ID;				// ID of the email job
	public $Started;			// Timestamp when sending started (won't be null; no timestamp = 0)
	public $Ended;				// Timestamp when sending ended (whether failed or succeeded) (none = 0)
	public $MemberID;			// ID of sending member, if it was a member
	public $StakeLeaderID;		// ID of sending stake leader, if it was a leader
	private $SenderName;		// For preserving the name of the sender
	private $SenderEmail;		// For preserving the email of the sender
	private $Recipients;		// Comma-separated list of recipients with their names
	public $Subject;			// Message subject
	public $Message;			// Contents of the message (can be plaintext or HTML)
	public $IsHTML;				// Whether this is an HTML message (usually plaintext)
	private $FailedRecipients;	// Recipients the message didn't get to, if any

	// Returns a populated EmailJob object if the ID is good.
	public static function Load($id)
	{
		$id = DB::Safe($id);
		$q = "SELECT * FROM EmailJobs WHERE ID='$id' LIMIT 1";
		$r = DB::Run($q);
		if (!$r)
			fail(mysql_error());
		$emailjob = mysql_fetch_object($r, 'EmailJob');
		return $emailjob;
	}

	// Saves this EmailJob in the database
	public function Save()
	{
		$q = DB::BuildSaveQuery($this, get_object_vars($this), false);
		$r = DB::Run($q);
		if (!$this->ID)
			$this->ID = mysql_insert_id();
		return $r ? true : false;
	}

	// Removes this email job from the database -- should only be done on rare occasions.
	// We preserve a log of sent emails for troubleshooting and to help combat
	// any potential abuse/security/privacy exploits.
	public function Delete()
	{
		if (!$this->ID)
			return false;

		return DB::Run("DELETE FROM EmailJobs WHERE ID='$this->ID' LIMIT 1");
	}


	// Adds a name/email to the recipients list, returns true if successful.
	public function AddRecipient($name, $email)
	{
		if (!$name || !$email)
			return false;
		
		if (!$this->Recipients)
			$this->Recipients = "";

		$this->Recipients .= ",{$email}={$name}";
		$this->Recipients = trim($this->Recipients, ', ');
		return true;
	}

	// Adds a name/email to the failed recipients list, returns true if successful.
	public function AddFailedRecipient($name, $email)
	{
		if (!$name || !$email)
			return false;
		
		if (!$this->FailedRecipients)
			$this->FailedRecipients = "";

		$this->FailedRecipients .= ",{$email}={$name}";
		$this->FailedRecipients = trim($this->FailedRecipients, ', ');
		return true;
	}

	// If a MEMBER already has a job in the queue (not finished), returns true
	// Specify "$max" if you want to allow more than 1 at a time. 1 is default.
	public static function UnfinishedJobExistsWithMemberID($id, $max = 1)
	{
		$id = DB::Safe($id);
		$r = DB::Run("SELECT ID FROM EmailJobs WHERE MemberID='$id' AND Ended = 0 LIMIT {$max}");
		return mysql_num_rows($r) >= $max;
	}

	// If a stake leader (by $id) already has a job in the queue (not finished), return true
	// Specify "$max" if you want to allow more than 1 at a time. 1 is default.
	public static function UnfinishedJobExistsWithLeaderID($id, $max = 1)
	{
		$id = DB::Safe($id);
		$r = DB::Run("SELECT ID FROM EmailJobs WHERE StakeLeaderID='$id' AND Ended = 0 LIMIT {$max}");
		return mysql_num_rows($r) >= $max;
	}

	// Saves the object and starts the sending job,
	// which runs in the background. No return value.
	// Will not start if already started or ended or
	// if some vital information is missing.
	public function Start()
	{
		// Necessary fields must be filled out
		if ($this->Started > 0
			|| $this->Ended > 0
			|| (!$this->MemberID && !$this->StakeLeaderID)
			|| !$this->Subject
			|| !$this->Message
			|| !$this->Recipients)
			return;

		// Populate the sender name and email fields for preservation purposes
		if ($this->IsMemberSender())
		{
			$mem = Member::Load($this->MemberID);
			$this->SenderName = $mem->FirstName()." ".$mem->LastName;
			$this->SenderEmail = $mem->Email;
		}
		else
		{
			$leader = StakeLeader::Load($this->StakeLeaderID);
			$this->SenderName = $leader->Title." ".$leader->LastName;
			$this->SenderEmail = $leader->Email;
		}

		// We leave sendemails.php to set and save the "start" timestamp; we don't do it here.
		$this->Save();

		// Call the worker process to run in the background. We pass in the ID
		// of the EmailJob so it can load all its info and process it. The worker
		// process sends the emails at a throttled rate.
		// The & tells it to go into the background, and the /dev/null thing
		// means any output can be discarded. The funky string "DKQl..." is a
		// password for internal use to help verify that the request is a valid one
		// from a legit source.
		$docroot = DOCROOT;
		$pwd = EMAIL_JOB_PASSWORD;
		$cmd = "php $docroot/api/sendemails.php $this->ID $pwd";
		exec("/usr/bin/nohup $cmd &> error_log &");
	}

	public function Done()
	{
		return $this->Ended > 0;
	}

	public function IsMemberSender()
	{
		return $this->MemberID > 0;
	}

	public function IsLeaderSender()
	{
		return $this->StakeLeaderID > 0;
	}

	// Transforms the pseudo-CSV list of recipients into a usable array
	public function RecipientsAsArray()
	{
		return $this->SplitDelimitedList($this->Recipients);
	}

	// Transforms the pseudo-CSV list of failed recipients into a usable array
	public function FailedRecipientsAsArray()
	{
		return $this->SplitDelimitedList($this->FailedRecipients);
	}

	// Splits a list in our special delimited format, into name/email arrays.
	// String format: "email@address.com=Name Here,next@email.com=Next Name,..."
	// We assume that = and , are not valid characters in a name or email. Code
	// elsewhere should have already stripped them out if necessary.
	// TODO: Why am I not just using JSON...??
	private function SplitDelimitedList($listAsString)
	{
		$recipients = array();
		$recips = explode(',', $listAsString);

		foreach ($recips as $recip)
		{
			$info = explode('=', $recip);
			
			if (!count($info))
				continue;

			$email = $info[0];
			$name = $info[1];
			
			$recipients[] = array("name" => $name, "email" => $email);
		}

		return $recipients;
	}

	// Returns the sender name
	public function SenderName()
	{
		return $this->SenderName;
	}

	// Returns the sender email address
	public function SenderEmail()
	{
		return $this->SenderEmail;
	}

	// Returns the ID of this object
	public function ID()
	{
		return $this->ID;
	}
}

?>