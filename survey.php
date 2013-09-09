<?php
require_once("lib/init.php");
protectPage();

// Get a list of all visible questions
$q = "SELECT ID FROM SurveyQuestions WHERE Visible=1 AND WardID={$MEMBER->WardID} ORDER BY ID ASC";
$r = DB::Run($q);

// Current member
$mem = Member::Load($_SESSION['userID']);

?>
<!DOCTYPE html>
<html>
	<head>
		<title>My survey &mdash; <?php echo SITE_NAME; ?></title>
		<?php include "includes/head.php"; ?>
	</head>
	<body>
		<?php include "includes/header.php"; ?>

		<form method="post" action="/api/saveanswers">
			
			<div class="text-center">
			<?php
			// Just finished registration? Make sure they feel like they're not
			// done until the survey is filled out.
			if (isset($_SESSION['isNew'])):
			?>
				<h1>Register</h1>
				<b>Please fill out this survey</b> and then you're done! Thank&nbsp;you.
				<hr class="line">
			<?php else: ?>
				<h1>Survey answers</h1>
			<?php endif; ?>
			</div>

			<div class="grid-container" style="font-size: 16px;">

				<div class="grid-100 text-center" style="font-size: 12px;">
					<i>Questions marked with <span class="req">*</span> require an answer.</i>
					<hr>
				</div>

<?php
$i = 0;

// Display questions / answers
while ($row = mysql_fetch_array($r)):

	// Load the question...
	$sq = SurveyQuestion::Load($row['ID']);

	// Load this member's answer
	$ans = $sq->Answers($mem->ID());

	// Create the name for this question's answer's input field
	$inputName = 'answers['.$sq->ID().']'; // Array idx is question ID

	// Is this question designed to have radio buttons or checkboxes?
	$multAnsOpt = $sq->QuestionType == QuestionType::MultipleChoice
		|| $sq->QuestionType == QuestionType::MultipleAnswer;

	// Okay, now which one?
	$inputType = "radio";
	if ($sq->QuestionType == QuestionType::MultipleAnswer)
	{
		$inputType = "checkbox";
		$inputName .= '[]'; // Store each check, not just the last one
	}

	$inputID = "field".$i;

	// Get answer options if needed
	$ansOpt = array();
	if ($multAnsOpt)
		$ansOpt = $sq->AnswerOptions();

	// Does this question have more than one answer, potentially?
	$multAns = $sq->QuestionType == QuestionType::MultipleAnswer
		|| $sq->QuestionType == QuestionType::CSV;
?>
				<div class="grid-50" style="padding-bottom: 20px;">
					<b>
						<label for="<?php echo $inputID; ?>">
							<?php echo $sq->Question; ?>
						</label>
					</b>
					<?php if ($sq->Required) echo '<span class="req">*</span>'; ?>
				</div>

				<div class="grid-50">
<?php
	// Format and display answer field(s) for this question depending on its type
	if ($sq->QuestionType == QuestionType::FreeResponse):
?>
					<textarea rows="1" name="<?php echo $inputName; ?>" id="<?php echo $inputID; ?>" placeholder="Type your answer here"><?php echo is_object($ans) ? $ans->AnswerValue : ''; ?></textarea>
<?php
	elseif ($sq->QuestionType == QuestionType::MultipleChoice
			|| $sq->QuestionType == QuestionType::MultipleAnswer):

		foreach ($ansOpt as $opt)
		{
			echo '<label><input type="'.$inputType.'" value="'.htmlentities($opt->AnswerValue()).'" name="'.$inputName.'"';
			echo 'data-label="'.$opt->AnswerValue().'"';
			$ansArray = $ans ? $ans->AnswerArray() : array();
			foreach($ansArray as $oneAns)
			{
				if (trim($oneAns) == $opt->AnswerValue())
				{
					echo ' checked';
					break;
				}
			}
			echo '><br>';
		}
	elseif ($sq->QuestionType == QuestionType::YesNo):
?>
						<input type="radio" name="<?php echo $inputName; ?>" value="Yes" data-label="Yes"<?php echo is_object($ans) ? $ans->AnswerValue == "Yes" ? 'checked' : '' : ''; ?>></label>
						<br>
						<label><input type="radio" name="<?php echo $inputName; ?>" value="No" data-label="No"<?php echo is_object($ans) ? $ans->AnswerValue == "No" ? 'checked' : '' : ''; ?>></label>
<?php
	elseif ($sq->QuestionType == QuestionType::ScaleOneToFive):
?>
						<input type="range" min="1" max="5" value="<?php echo is_object($ans) && is_numeric($ans->AnswerValue) ? $ans->AnswerValue : '3' ?>" step="1" id="<?php echo $inputName; ?>" name="<?php echo $inputName; ?>">
						<output id="<?php echo $sq->ID(); ?>"><?php echo is_object($ans) && is_numeric($ans->AnswerValue) ? $ans->AnswerValue : '3' ?></output>
<?php
	elseif ($sq->QuestionType == QuestionType::Timestamp):
?>
						<input type="text" name="<?php echo $inputName; ?>" class="timestamp" value="<?php echo is_object($ans) ? date('d M Y, g:i A', strtotime($ans->AnswerValue)) : '' ?>" placeholder="Type a date and/or time">
<?php
	elseif ($sq->QuestionType == QuestionType::CSV):
?>
						<!--Please list <i>one per line</i>:<br>-->
						<textarea rows="1" name="<?php echo $inputName; ?>" placeholder="List one per line"><?php echo formatForClient(is_object($ans) ? $ans->AnswerValue : ''); ?></textarea>
<?php
	else:
		echo '';
	endif;
?>

					</div>

					<hr class="clear"><br><br>
<?php
	$i ++;
	endwhile;
?>

					<div class="text-center">
						<button type="submit"><?php echo isset($_SESSION['isNew']) ? "Finish" : "Save"; ?></button>
						<br><br>
					</div>

				</div>

			</form>

			<?php include "includes/footer.php"; ?>

		<?php include "includes/nav.php"; ?>

<script>

$(function()
{
	// Show the value of sliders on page load
	$('output').each(function() {
		$(this).text($(this).prev().val());
	});

	// As sliders are moved, change the value displayed by them.
	$('input[type=range]').change(function() {
		$(this).next('output').text($(this).val());
	});

	// Verify date/time inputs as they're entered
	$('.timestamp').change(function()
	{
		$.get('/api/tryparsedate.php', {
			input: $(this).val()
		})
		.success(function()
		{
			$(this).css('color', '');
			$('[type=submit]').prop('disabled', false);
		})
		.fail(function(jqxhr)
		{
			$(this).css('color', '#CC0000');
			$('[type=submit]').prop('disabled', true);
			$.sticky(jqxhr.responseText || "Please type a better date, for example: July 3, 1990.", { classList: "error" });
		});
	});

	// Resize the textarea vertically as it gets filled out
	$('textarea').keyup(function()
	{
		$(this).css('height', 'auto').css('height', this.scrollHeight+'px');
	});


	// Capture form submit and save the answers
	$('form').hijax({
		before: function() {
			$('[type=submit').showSpinner();
		},
		complete: function(xhr) {
			console.log(xhr);
			if (xhr.status == 200)
			{
				_gaq.push(['_trackEvent', 'Account', 'Submit Form', 'Survey Answers']);
				$.sticky(xhr.responseText || "Saved your survey. thanks!");
				if (xhr.responseText.toLowerCase().indexOf("welcome") > -1)		// New member just finished registering; take to directory.
					setTimeout(function() { window.location = '/directory'; }, 3000);
			}
			else
				$.sticky(xhr.responseText || "There was a problem and your survey was probably not saved. Check your Internet connection and try again.", { classList: "error" });

			$('[type=submit').hideSpinner();
		}
	});
});

</script>
	</body>
</html>