<?php
require_once("../../lib/init.php");
protectPage(8);

// Grab the variables from the form
@ $question = $_POST['question'];
@ $qtype = $_POST['qtype'];
$ansArray = isset($_POST['ans']) ? $_POST['ans'] : null;
$req = isset($_POST['req']) ? true : false;
$visible = isset($_POST['visible']) ? true : false;

// Validation
if (!$question || strlen(trim($question)) < 3)
	Response::Send(401, "Oops - did you type a question (at least 3 characters long)? Go BACK and try again.");

// Ready the answer options array; is it empty?
$ansEmpty = true;
if ($ansArray)
{
	foreach ($ansArray as &$opt)
	{
		$opt = trim($opt);
		if ($opt != '')
		{
			$ansEmpty = false;
			break;
		}
	}
}

// Is this question designed to have answer choices/options?
$multAns = ($qtype == QuestionType::MultipleChoice
		|| $qtype == QuestionType::MultipleAnswer);

// Make sure that multiple-answer/choice questions have at least one
// to choose from
if ($multAns && $ansEmpty)
{
	Response::Send(401, "Oops - for that type of question, it requires at least one possible answer (you have to add one). Go BACK and try again.");
}

// Create question.
$qu = new SurveyQuestion();
$qu->Question = $question;
$qu->QuestionType = $qtype;
$qu->Required = $req;
$qu->Visible = $visible;
$qu->WardID = $MEMBER->WardID;

// Save what we have (it needs an ID in order to add answer options)
if (!$qu->Save())
	fail("Could not save this question. Please report this and try again...");


// Add answer options, if applicable
if ($multAns)
{
	foreach ($ansArray as $ans)
	{
		if (strlen(trim($ans)) > 0 && $ans != ' ' && !$qu->AddAnswerOption($ans))
			echo("Could not add answer $ans to this question; go back, refresh, and add this answer manually.");
	}
}

// Set session variable so the user is notified of success, then redirect
$_SESSION['created'] = true;
header("Location: ../survey.php");


?>