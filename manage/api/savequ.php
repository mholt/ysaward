<?php
require_once("../../lib/init.php");
protectPage(8);

// This file saves changes to a survey question.

// Grab the variables from the form
@ $question = $_POST['question'];
@ $qtype = $_POST['qtype'];
@ $qid = $_POST['qid'];
$ansArray = isset($_POST['ans']) ? $_POST['ans'] : null;
$req = isset($_POST['req']) ? true : false;
$visible = isset($_POST['visible']) ? true : false;
$delete = isset($_POST['delete']) ? true : false;

// Validation
if (!$qid)
	fail("No question ID found. Must abort... please report this.");

if (!$question || strlen(trim($question)) < 3)
	Response::Send(401, "The question must be at least 3 characters long.");

// Load existing question
$existing = SurveyQuestion::Load($qid);

// Validate it...
if (!is_object($existing))
	fail("Not a valid question was loaded, so thus it can't be changed! (Is the ID $qid correct?)");

// Make sure it's in the same ward...
if ($existing->WardID != $MEMBER->WardID)
	Response::Send(403, "You may only change questions which belong to your ward.");

// Are we to delete this question?
if ($delete)
{
	if (!$existing->Delete(true))
		fail("Could not delete question... not sure why... do report this, though.");
	Response::Send(200);
}

// Load existing answer options for this question
$answerOptions = $existing->AnswerOptions();

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

// Is this (updated, existing) question designed to have answer choices/options?
$multAns = $qtype == QuestionType::MultipleChoice || $qtype == QuestionType::MultipleAnswer;

// Is it no longer multiple-answer/choice?
if (!$multAns && ($existing->QuestionType == QuestionType::MultipleChoice
	|| $existing->QuestionType == QuestionType::MultipleAnswer))
{
	// Delete existing AnswerOptions for this question as they no longer
	// apply for the new QuestionType.
	// Even though the SurveyQuestion class has a function to do this,
	// we're doing it manually to save a few DB queries
	foreach ($answerOptions as $ansOpt)
		$existing->DeleteAnswerOption($ansOpt->ID());
}

// Make sure that a multiple-answer/choice question has at least one
// to choose from.
if ($multAns && $ansEmpty)
	die("Oops - for this type of question, it requires at least one possible answer (you have to add one). Go BACK and try again.");

if ($multAns && !$ansEmpty)
{
	// Generate a new set of answer options for this question
	// We're doing it manually for the same reason as above
	foreach ($answerOptions as $ansOpt)
		$existing->DeleteAnswerOption($ansOpt->ID());
	foreach ($ansArray as $ans) // Create new
		if (strlen(trim($ans)) > 0 && trim($ans) != ' ')
			$existing->AddAnswerOption($ans);
}


// Modify question.
$existing->Question = $question;
$existing->QuestionType = $qtype;
$existing->Required = $req;
$existing->Visible = $visible;

// Answer options have already been saved; now
// save the question itself.
// Save what we have (it needs an ID in order to add answer options)
if (!$existing->Save())
	fail("Could not save changes to this question. Please report this and maybe try again...");


Response::Send(200);

?>