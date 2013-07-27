<?php

/**
 * @author Matthew Holt
 */


class SurveyAnswer
{
	private $ID;
	private $QuestionID;
	private $MemberID;
	public $AnswerValue;
	
	const DELIM = "\r\n";
	
	// Returns a populated SurveyAnswer object if the ID is good.
	public static function Load($id)
	{
		$q = "SELECT * FROM SurveyAnswers WHERE ID='$id' LIMIT 1";
		$r = DB::Run($q);
		if (!$r)
			fail(mysql_error());
		$ans = mysql_fetch_object($r, 'SurveyAnswer');
		return $ans;
	}
	
	public function Save()
	{
		// Should we be able to save multiple answers to the
		// same question for the same member? No.
		// Make safe the user's input, then let's prevent duplication.
		if (is_array($this->AnswerValue))
			$this->AnswerArrayToString();
		
		$safeAns = DB::Safe($this->AnswerValue);	// Strips HTML tags, just be aware of that.
		$q = "SELECT 1 FROM SurveyAnswers WHERE AnswerValue='$safeAns' AND QuestionID='$this->QuestionID' AND MemberID='$this->MemberID' AND ID!='$this->ID' LIMIT 1";
		if (mysql_num_rows(DB::Run($q)) > 0)
			fail("Oops. Could not save this answer; this user has already answered that question, but instead of changing the existing answer we tried adding a new one. Huh.");
		
		$q = DB::BuildSaveQuery($this, get_object_vars($this));
		$r = DB::Run($q);
		if (!$this->ID)
			$this->ID = mysql_insert_id();
		return $r ? true : false;
	}
	
	// Deletes this answer from the database permanently.
	// Returns true on success, otherwise false.
	public function Delete()
	{
		if (!$this->ID)
			return false;
		$q = "DELETE FROM SurveyAnswers WHERE ID='$this->ID' LIMIT 1";
		return DB::Run($q);
	}
	
	// Returns the AnswerValue as an HTML-formatted
	// read-only version of the answer. Makes it look good.
	// Does not affect actual AnswerValue property.
	public function ReadonlyAnswer()
	{
		$display = $this->AnswerValue;
		if (is_array($display))
			$display = implode(self::DELIM, $this->AnswerValue);
		
		// Second argument not supported before PHP 5.3
		return phpVersionInt() >= 503000 ? nl2br($display, false) : nl2br($display);
	}
	
	// For answers that are to questions which are
	// multiple-answer or CSV, which both have multiple
	// answers stored in one field, this parses out
	// the different answers into an array and returns it.
	// AnswerValue remains a string.
	public function AnswerArray()
	{
		return explode(self::DELIM, $this->AnswerValue);
	}
	
	// In the even that checkboxes are used for the answer,
	// for example, or there are multiple answers, this 
	// changes the answer from an array to a newline-delimited
	// string, ready to save in the DB.
	public function AnswerArrayToString()
	{
		if (!is_array($this->AnswerValue))
			return false;
		
		// Trim extra spaces and empty items
		foreach ($this->AnswerValue as $key => &$val)
		{
			if (!$val)
				unset($this->AnswerValue[$key]);
			else
				$val = trim($val);
		}
		
		$this->AnswerValue = implode(self::DELIM, $this->AnswerValue);
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
	
	// Returns the ID of the member who owns this answer
	// To change/set it, pass in a variable here.
	public function MemberID($setTo = null)
	{
		if ($setTo) $this->MemberID = $setTo;
		return $this->MemberID;
	}
}

?>