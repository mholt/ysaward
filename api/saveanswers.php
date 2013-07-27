<?php
require_once("../lib/init.php");
protectPage();

// Process survey responses and save or update them.
@ $answers = $_POST['answers'];
$memID = $MEMBER->ID();

// Make sure the array isn't empty
if (!$answers || empty($answers))
	Response::Send(400, "No answers to save! If this is a mistake, please report it and we'll take a look.");

// Make sure it's not all empty strings, too.
$allEmpty = true;
foreach ($answers as $answer)
{
	if (is_array($answer) && isset($answer[0]) && strlen($answer[0]) > 0
		|| is_string($answer) && strlen(trim($answer)) > 0)
	{
		$allEmpty = false;
		break;
	}
}

if (empty($answers) || $allEmpty)
	Response::Send(400, "All the answers submitted are left blank...");



// Go through and save the answer(s) to each question.
// We'll skip required ones if an answer is missing to it,
// and catch it later. But let's save what we can so users
// don't have to re-answer all the questions (non-destructive)!
foreach ($answers as $qid => $ans)
{
	// Make sure the question is visible in order to save.
	// "Invisible" questions' answers must be safe from any changes.
	$q = "SELECT 1 FROM SurveyQuestions WHERE ID='$qid' AND Visible=1 LIMIT 1";
	if (mysql_num_rows(DB::Run($q)) == 0)
		continue;

	$sq = SurveyQuestion::Load($qid);	// Load question
	$ansObj = $sq->Answers($memID);		// Load this members' answer to it

	$ansChanged = false;

	// Make sure it's not a required question with an empty
	// or no answer. If so, skip it.
	if ($sq->Required && !$ans)
		continue;

	// For Timestamp-style answers, format them accordingly
	if ($sq->QuestionType == QuestionType::Timestamp)
		$ans = sqldate($ans);

	// Free-response should be capitalized at the beginning
	if ($sq->QuestionType == QuestionType::FreeResponse)
		$ans = ucfirst(trim($ans));

	if (!$ansObj)
	{
		// No previous answer? No problem! Create it.
		$ansObj = $sq->AddAnswer($ans, $memID);
		$ansChanged = true;
	}
	else
	{
		// Update existing answer if necessary
		if ($ans != $ansObj->AnswerValue)
		{
			$ansObj->AnswerValue = $ans;
			$ansChanged = true;
		}
	}

	if ($ansChanged)
	{
		// For checkbox questions, make sure the answers
		// are there as a single string (not an array)
		if ($sq->QuestionType == QuestionType::MultipleAnswer)
			$ansObj->AnswerArrayToString();

		// Ensure that line break delimeters are consistent.
		//
		// ** Right now we receive a CSV field as a single string.
		// If it ever changes so that it's POSTed as an array,
		// we can combine this line with the one above it
		// like this:
		//
		// if ($sq->QuestionType == QuestionType::MultipleAnswer)
		// 		|| $sq->QuestionType == QuestionType::CSV)
		//		$ansObj->AnswerArrayToString();
		//
		// (Remember, too, that the check to see if the answer changed
		// above may need updating for CSV if we pass it in as array instead)
		// For now, though, just normalize the line breaks.
		if ($sq->QuestionType == QuestionType::CSV)
		{
			$ansObj->AnswerValue = formatForDB($ansObj->AnswerValue);

			// Also capitalize the first letter of each list item
			$ansObj->AnswerValue = explode(SurveyAnswer::DELIM, $ansObj->AnswerValue);
			foreach ($ansObj->AnswerValue as &$item)
				$item = ucfirst(trim($item));
			$ansObj->AnswerArrayToString();
		}

		// Save the answer now.
		$ansObj->Save();
	}
}

// Identify un-answered questions, both required and not.
// We poll the DB because un-checked checkboxes aren't submitted
// at all, so we have to manually check if they're missing.
// If the question requires an answer, enforce that requirement.
// If the question is not required, give un-filled answers an empty value.
// (This whole block isn't very efficient way to do this, but for
// the low traffic volume we get, it should be fine.... for now...
// especially considering how quickly this had to be ready!)
$q = "SELECT ID FROM SurveyQuestions WHERE WardID={$MEMBER->WardID} AND Visible='1'";
$r = DB::Run($q);
while ($row = mysql_fetch_array($r))
{
	// Find out about the question and the user's answer to it, if any
	$reqQu = SurveyQuestion::Load($row['ID']);
	$userAns = isset($answers[$reqQu->ID()]) ? $answers[$reqQu->ID()] : null;
	if (is_string($userAns))
		$userAns = trim($userAns);

	// If it IS required, and not answered, time to throw.
	if ($reqQu->Required
		&& (
			!$userAns
			|| (!is_array($userAns) && strlen(trim($userAns)) == 0)
			|| $userAns == ' '
			)
		)
		Response::Send(400, "Please answer the required question:<br><br>\"".$reqQu->Question."\"");

	// If NOT required, set to empty value if not filled out
	if (!$reqQu->Required
		&& (
			!$userAns
			|| (!is_array($userAns) && strlen(trim($userAns)) == 0)
			|| $userAns == ' '
			)
		)
	{
		// First we have to get it from the DB.
		$ansObj = $reqQu->Answers($memID);

		// TODO: FIX THIS:
		// I added this if statement because this block was causing errors
		// in the error log (a lot of them):
		// [06-Jan-2012 10:54:59] PHP Fatal error: Call to undefined method stdClass::Save() in /home5/ysatwoze/public_html/save.php on line 157
		// ($ansObj->Save() used to be on line 157). It appears that $ansObj
		// was null or a empty class or something... (a "standard" class)
		// NOTE: That happens when the member has not answered the question.
		if (isset($ansObj) && get_class($ansObj) == "SurveyAnswer")
		{
			$ansObj->AnswerValue = '';
			$ansObj->Save();
		}
	}
}

// Update the user's LastUpdated timestamp.
// It's not super-critical, so suppress any errors.
$MEMBER->Save(true);

// 200 OK (we're done here)
if (isset($_SESSION['isNew']))
{
	// Member is no longer a "new" member... (registration complete)

	// ** IMPORTANT NOTE ** Do not change the text of this response as the receiving
	// Javascript relies on its contents to know to redirect. Specifically: "Welcome" (case-sensitive)
	// That's lame, I know, but it works and I like cheese.

	unset($_SESSION['isNew']);
	Response::Send(200, "Thank you for signing up. Welcome to the ward!<br><br>Redirecting you...");
}
else
	Response::Send(200, "Saved your survey answers");

?>