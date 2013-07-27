<?php

/**
 * @author Matthew Holt
 */

 // UI issue: if an answer option is edited/changed,
 // provide the option to sync all existing answers
 // of this value for this question to the new answer value.
 // But in some cases it would destroy data integrity (e.g.
 // an answer value is a polar opposite for some reason...
 // in that case, members' original answers should be kept.)

class SurveyAnswerOption
{
	private $ID;
	private $QuestionID;
	private $AnswerValue;
	
	// Returns a populated SurveyAnswerOption object if the ID is good.
	public static function Load($id)
	{
		$q = "SELECT * FROM SurveyAnswerOptions WHERE ID='$id' LIMIT 1";
		$r = DB::Run($q);
		if (!$r)
			fail(mysql_error());
		$ans = mysql_fetch_object($r, 'SurveyAnswerOption');
		return $ans;
	}
	
	public function Save()
	{
		// Can we have multiple answer options of the exact
		// same value for the same question?
		// Right now... NO.
		
		// Make safe the answer value before our preliminary query (including stripping HTML tags)
		$safeAns = DB::Safe($this->AnswerValue);
		
		$q = "SELECT 1 FROM SurveyAnswerOptions WHERE QuestionID='$this->QuestionID' AND AnswerValue='$safeAns' LIMIT 1";
		if (mysql_num_rows(DB::Run($q)) > 0)
			fail("Hmmm, this answer option ($this->AnswerValue) already exists for this question. Are you sure you didn't mean something else?");
		
		
		$q = DB::BuildSaveQuery($this, get_object_vars($this));
		$r = DB::Run($q);
		if (!$this->ID)
			$this->ID = mysql_insert_id();
		return $r ? true : false;
	}
	
	// Returns the ID of this object
	public function ID()
	{
		return $this->ID;
	}
	
	// Returns the ID of the question this answers
	// To change/set it, pass in a variable here.
	public function QuestionID($setTo = null)
	{
		if ($setTo) $this->QuestionID = $setTo;
		return $this->QuestionID;
	}
	
	
	/**
	 * Changes/sets or retrieves the value of this answer option...
	 *
	 * @var $setTo If set, will change the answer to this value.
	 * @var $syncExisting If true, will update all existing members'
	 	SurveyAnswers of the same value for this question to comply
	 	with this new value. WARNING: This may have drastic adverse
	 	effects if used erroneously or by mistake.
	 * @return The (new) answer option value
	*/
	public function AnswerValue($setTo = null, $syncExisting = false)
	{
		/* TODO: Code this sync feature*/
		if ($setTo) $this->AnswerValue = $setTo;
		return $this->AnswerValue;
	}
}

?>