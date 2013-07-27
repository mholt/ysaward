<?php

/**
 * @author Matthew Holt
 */

class SurveyQuestion
{
	private $ID;			// The unique ID. Required (generated).
	public $Question;		// A string: the question. Required.
	public $QuestionType;	// int of the QuestionType enum
	public $Required;		// 0 by default (or weak-typed false)
	public $Visible = true;	// Question is active or not?
	public $WardID;			// The ID of the ward of this question. Required.

	// Returns a populated SurveyQuestion object if the ID is good.
	public static function Load($id)
	{
		$id = DB::Safe($id);
		$q = "SELECT * FROM SurveyQuestions WHERE ID='$id' LIMIT 1";
		$r = DB::Run($q);
		if (!$r)
			fail(mysql_error());
		$question = mysql_fetch_object($r, 'SurveyQuestion');
		return $question;
	}

	// Saves this question in the database
	public function Save()
	{
		// The ward ID and question content is required!
		if (!$this->WardID || !trim($this->Question))
			fail("ERROR > Cannot save this question without a ward ID and question text.");

		if (!Ward::Load($this->WardID))
			fail("ERROR > Cannot save question \"".$this->Question."\" because the ward ID (".$this->WardID.") is found to be invalid.");

		// Make sure the question is unique
		$safeQ = DB::Safe($this->Question);
		$q = "SELECT 1 FROM SurveyQuestions WHERE Question='$safeQ' AND WardID='$this->WardID' AND ID!='$this->ID' LIMIT 1";
		if (mysql_num_rows(DB::Run($q)) > 0)
			fail("Oops. Could not save question; that question already exists in this ward.");

		$q = DB::BuildSaveQuery($this, get_object_vars($this));
		$r = DB::Run($q);
		if (!$this->ID)
			$this->ID = mysql_insert_id();
		return $r ? true : false;
	}

	// Add an answer to this question.
	public function AddAnswer($ansValue, $memberID)
	{
		$ans = new SurveyAnswer();
		$ans->QuestionID($this->ID);
		$ans->MemberID($memberID);
		$ans->AnswerValue = $ansValue;
		$ans->Save();
		return $ans;
	}

	// Create a SurveyAnswerOption object and associate it
	// with this SurveyQuestion object in the database.
	// Returns the new SurveyAnswerOption object
	public function AddAnswerOption($answerValue)
	{
		if (!$this->ID)
			return null;
		$ansOpt = new SurveyAnswerOption();
		$ansOpt->AnswerValue($answerValue);
		$ansOpt->QuestionID($this->ID);
		$ansOpt->Save();
		return $ansOpt;
	}

	// Permanently delete an answer option from the DB
	// It must belong to this question
	public function DeleteAnswerOption($ansOptID)
	{
		// Make sure that answer option belongs to this question
		$q = "SELECT 1 FROM SurveyAnswerOptions WHERE QuestionID='$this->ID' AND ID='$ansOptID' LIMIT 1";
		if (mysql_num_rows(DB::Run($q)) == 0)
			return false;

		// Perform the delete
		$q = "DELETE FROM SurveyAnswerOptions WHERE ID='$ansOptID' LIMIT 1";
		return DB::Run($q);
	}

	// Deletes all existing answeroptions for this question.
	// Pass in boolean true to be sure and succeed.
	public function DeleteAllAnswerOptions($sure = false)
	{
		if ($sure !== true)
			fail("Cannot delete all answer options to this question; please specify TRUE to be sure.");

		foreach ($this->AnswerOptions() as $existingAnsOpt)
			if (!$this->DeleteAnswerOption($existingAnsOpt->ID()))
				fail("Could not delete all answer options... something went wrong. Please report this.");

		return true;
	}

	// Pass in an AnswerValue (string, usually) to see if that is
	// an exact match of an existing AnswerOption for this question.
	// Optionally pass in an array of SurveyAnswerOption objects.
	// RETURNS THE ID OF THE MATCHING SURVEYANSWEROPTION OBJECT if found,
	// false otherwise.
	public function HasAnswerOption($optVal, $opts = array())
	{
		if (empty($opts))
			$opts = $this->AnswerOptions();

		foreach ($opts as $opt)
			if (is_object($opt) && $opt->AnswerValue == $optVal)
				return $opt->ID();

		return false;
	}

	// Returns an array of SurveyAnswerOption objects associated with this question
	public function AnswerOptions()
	{
		$q = "SELECT * FROM SurveyAnswerOptions WHERE QuestionID='$this->ID'";
		$r = DB::Run($q);

		$opts = array();
		while ($opt = mysql_fetch_object($r, 'SurveyAnswerOption'))
			array_push($opts, $opt);

		return $opts;
	}

	// Returns an array of the SurveyAnswer objects that
	// are associated with this question
	// Optionally pass in a member's ID to filter.
	// If there is only one answer, it returns only that SurveyAnswer
	// object, not an array of one object.
	public function Answers($memberID = 0)
	{
		$memberID = DB::Safe($memberID);
		$q = "SELECT ID FROM SurveyAnswers WHERE QuestionID='$this->ID'";
		if ($memberID > 0)
			$q .= " AND MemberID='$memberID'";
		$r = DB::Run($q);

		$ans = array();
		while ($answerID = mysql_fetch_array($r))
			array_push($ans, SurveyAnswer::Load($answerID['ID']));

		// When getting a specific members' answers, only return the
		// single answer if there is just one. Much easier to work with.
		if (mysql_num_rows($r) == 1 && $memberID > 0)
			return $ans[0];
		elseif (mysql_num_rows($r) == 0 && $memberID > 0)
			return null;
		else
			return $ans;
	}

	// Deletes this question, including all answers to it
	// and all answer options, and all permissions associated
	// with it. Must pass in boolean true to ensure success.
	// Returns true upon success.
	public function Delete($sure = false)
	{
		if ($sure !== true)
			fail("Could not delete this question; pass in boolean true to be sure.");

		if (!$this->ID)
			fail("Could not delete this question, because no valid ID was associated with it.");

		// "Make safe the harbor!" ... or ... "Make safe the city!" (pick your movie; I prefer the latter)
		$safeID = DB::Safe($this->ID);

		// Delete all SurveyAnswerOptions to it
		$this->DeleteAllAnswerOptions(true);

		// Delete all permissions for it
		$q = "DELETE FROM Permissions WHERE QuestionID='$safeID'";
		if (!DB::Run($q))
			fail("Could not delete permissions for this question with ID {$this->ID}, reason: ".mysql_error());

		// Delete all answers to this question
		foreach ($this->Answers() as $ans)
			$ans->Delete();

		// Delete the question, at last.
		$q = "DELETE FROM SurveyQuestions WHERE ID='$safeID' LIMIT 1";
		if (!DB::Run($q))
			fail("Could not delete question with ID {$this->ID} from database (but answers, answer options, and permissions for it were all deleted), reason: ".mysql_error());

		return true;
	}

	// Returns the ID of this object
	public function ID()
	{
		return $this->ID;
	}
}

?>