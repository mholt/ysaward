<?php
require_once("lib/init.php");
protectPage();

// Get a list of all visible questions
$q = "SELECT ID FROM SurveyQuestions WHERE Visible=1 AND WardID={$MEMBER->WardID} ORDER BY ID ASC";
$r = DB::Run($q);

// Current member
$mem = Member::Load($_SESSION['userID']);

?>
<html>
<head>
	<title>My survey answers &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
	<?php include("includes/head.php"); ?>
</head>
<body>
	
	<?php include("includes/header.php"); ?>
	
	<article class="grid-12 group">
		
		<section class="g-12">
			
			<div class="text-center">
			<?php
			// Just finished registration? Make sure they feel like they're not
			// done until the survey is filled out.
			if (isset($_GET['new'])):
			?>
				<h1>registration (page 2 / 2)</h1>
				<p><b>Please fill out this survey</b> and then you're done! Thank you.</p>
			<?php else: ?>
				<h1>My survey answers</h1>
				<p><b>Switch to:</b> <a href="editprofile.php">Edit profile</a></p>
			<?php endif; ?>
			</div>

				<p style="font-style: italic;">Questions marked with <span class="req">*</span> require an answer.</p>

				<form method="post" action="api/saveanswers.php">
				<table class="formTable">
					<tr>
						<th colspan="2">Question<br><br></th>
						<th>My answer<br><br></th>
					</tr>
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

						// Okay, which one?
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
						<tr style="background: <?php echo $i % 2 == 0 ? '#EFEFEF' : '#FFF' ?>;">
							<td class="reqtd"><?php if ($sq->Required) echo '<span class="req">*</span>'; ?></td>
							<td class="qu"><label for="<?php echo $inputID; ?>"><?php echo $sq->Question; ?></label></td>
							<td>
				<?php
									// Format and display answer field(s) for this question.
									if ($sq->QuestionType == QuestionType::FreeResponse):
				?>
				<textarea rows="3" cols="45" name="<?php echo $inputName; ?>" id="<?php echo $inputID; ?>"><?php echo is_object($ans) ? $ans->AnswerValue : ''; ?></textarea>
								<?php
									elseif ($sq->QuestionType == QuestionType::MultipleChoice
											|| $sq->QuestionType == QuestionType::MultipleAnswer):

										foreach ($ansOpt as $opt)
										{
											echo "<label><input type=\"$inputType\" value=\"".htmlentities($opt->AnswerValue())."\" name=\"$inputName\"";
											$ansArray = $ans ? $ans->AnswerArray() : array();
											foreach($ansArray as $oneAns)
											{
												if (trim($oneAns) == $opt->AnswerValue())
												{
													echo ' checked="checked"';
													break;
												}
											}
											echo "> ".$opt->AnswerValue()."</label><br>";
										}
									elseif ($sq->QuestionType == QuestionType::YesNo):
								?>
									<label><input type="radio" name="<?php echo $inputName; ?>" value="Yes" <?php echo is_object($ans) ? $ans->AnswerValue == "Yes" ? 'checked="checked"' : '' : ''; ?>> Yes</label>
									<br>
									<label><input type="radio" name="<?php echo $inputName; ?>" value="No" <?php echo is_object($ans) ? $ans->AnswerValue == "No" ? 'checked="checked"' : '' : ''; ?>> No</label>
								<?php
									elseif ($sq->QuestionType == QuestionType::ScaleOneToFive):
								?>
									<input type="range" min="1" max="5" value="<?php echo is_object($ans) && is_numeric($ans->AnswerValue) ? $ans->AnswerValue : '3' ?>" step="1" id="<?php echo $inputName; ?>" name="<?php echo $inputName; ?>">
									<output id="<?php echo $sq->ID(); ?>"><?php echo is_object($ans) && is_numeric($ans->AnswerValue) ? $ans->AnswerValue : '3' ?></output>
								<?php
									elseif ($sq->QuestionType == QuestionType::Timestamp):
								?>
									<input type="text" size="35" name="<?php echo $inputName; ?>" value="<?php echo is_object($ans) ? $ans->AnswerValue : '' ?>">
								<?php
									elseif ($sq->QuestionType == QuestionType::CSV):
								?>
									Please list <i>one per line</i>:<br>
									<textarea rows="7" cols="15" name="<?php echo $inputName; ?>"><?php echo formatForClient(is_object($ans) ? $ans->AnswerValue : ''); ?></textarea>
								<?php
									else: echo ''; endif;
								?>


							</td>
						</tr>
				<?php
					$i ++;
					endwhile;
				?>
					</table>

					<div class="text-center">
						<br><br>
						<?php
							echo '<input type="submit" id="sub" value="&#10003; ';
							echo isset($_SESSION['isNew']) ? "Save and finish" : "Save survey answers";
							echo '" class="button">';
						?>
						<img src="images/ajax-loader.gif" style="visibility: hidden; position: relative; top: 10px; left: 10px;" id="ajaxloader">
						<br><br>
					</div>

				</form>

				<hr>
				<p class="text-center"><b>Switch to:</b> <a href="editprofile.php">Edit profile</a></p>


		</section>
		
	</article>

<script type="text/javascript">

$(function() {
	// Check if browser supports input type "range" (the slider)
	// Replace non-support with radio buttons
	var inputTest = document.createElement("input");
	inputTest.setAttribute("type", "range");
	var rangeNotSupported = (inputTest.type === "text");
	delete inputTest;
	if (rangeNotSupported)
	{
		$('input[step]').each(function() {
			var nm = $(this).attr('name');
			var ansVal = $(this).next('output').text(); // Existing answer's value
			var replacement = $('<input type="radio" value="1" name="' + nm + '"> 1<br><input type="radio" value="2" name="' + nm + '"> 2<br><input type="radio" value="3" name="' + nm + '"> 3<br><input type="radio" value="4" name="' + nm + '"> 4<br><input type="radio" value="5" name="' + nm + '"> 5<br>');
			$(this).next('output').remove();
			$(this).replaceWith(replacement);
			// For any existing answer, "check" it.
			$(':radio[name="' + nm + '"][value=' + ansVal + ']').prop('checked', true);
		});
	}

	// Show the value of sliders on page load
	$('output').each(function() {
		$(this).text($(this).prev().val());
	});

	// As slides are moved, change the value displayed by them.
	$('input[type=range]').change(function() {
		$(this).next('output').text($(this).val());
	});


	// Capture form submit and save the answers
	$('form').hijax({
		before: function() {
			$('#sub').prop('disabled', true);
			$('#ajaxloader').css('visibility', 'visible');
		},
		complete: function(xhr) {
			if (xhr.status == 200)
			{
				_gaq.push(['_trackEvent', 'Account', 'Submit Form', 'Survey Answers']);
				toastr.success(xhr.responseText);
				if (xhr.responseText.toLowerCase().indexOf("welcome") > -1)		// New member just finished registering; take to directory.
					setTimeout(function() { window.location = '/directory.php'; }, 3000);
			}
			else
				toastr.error(xhr.responseText || "There was a problem, and your survey was not saved. Please try again, then contact your ward website person if it continues.");

			$('#sub').prop('disabled', false);
			$('#ajaxloader').css('visibility', 'hidden');
		}
	});


});

</script>
	
<?php include("includes/footer.php"); ?>