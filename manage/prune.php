<?php
require_once "../lib/init.php";
protectPage(13);

$q = DB::Run("SELECT ID, FirstName, MiddleName, LastName, LastActivity, RegistrationDate FROM Members WHERE WardID={$MEMBER->WardID} ORDER BY FirstName, LastName ASC");

$months = array("January", "February", "March", "April",
	"May", "June", "July", "August", "September", "October",
	"November",	"December");



/** TEMPORARY (UNTIL THE AUTOMATIC DELETING FEATURE IS FINISHED) **/
$mems = array();
$q = "SELECT ID FROM Members WHERE WardID='$MEMBER->WardID' ORDER BY FirstName,LastName ASC";
$r = DB::Run($q);
while ($row = mysql_fetch_array($r))
	array_push($mems, Member::Load($row['ID']));
/** END TEMPORARY (more below) **/


?>
<!DOCTYPE html>
<html>
	<head>
		<title>Delete member accounts &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
		<?php include "../includes/head.php"; ?>
		<style>
		/** TEMPORARY **/
		#memberlist { font-size: 12px; }
		table label { display: block; padding: 3px; cursor: pointer; }
		table label:hover { background: #EEE; }
		#memberlist td { white-space: nowrap; vertical-align: top; padding-right: 15px; }
		[type=submit] { font-size: 20px !important; background: #CC0000 !important; }
		[type=submit]:hover { background: #FF0000 !important; }
		/** END TEMP **/


		.loader {
			visibility: hidden;
			vertical-align: bottom;
		}

		input[disabled] {
			background: #EEE;
		}

		.msgPreview {
			background: #DEDEDE;
			padding: 10px;
			max-width: 650px;
			margin-top: 15px;
		}

		.msgPreview p, #msgText {
			font-size: 14px;
			line-height: 1.5em;
			margin: 10px 0;
		}
		</style>
	</head>
	<body>
		<?php include "../includes/header.php"; ?>

		<h1>Delete accounts</h1>

		<div class="grid-container">

			<div class="grid-100">

				<!-- TEMPORARY -->

				<?php if (isset($_GET['success']) && $_GET['success'] == true): ?>
				<div style="color: #FFF; background: green; padding: 7px; text-shadow: none; text-align: center; border-radius: 5px;">
					Successfully deleted selected member accounts.
				</div><br>
				<?php endif; ?>

				<div class="instructions">
					<p>
						When ward membership changes so drastically between terms and semesters and the
						turnover rate is high, this page will help you delete the accounts of those
						who've moved out of the ward <i>en masse</i>.
					</p>

					<p>
						Simply check the names of members whose accounts should be removed. When you submit the
						form, this will happen immediately. Be careful here, since there is no undo button.
					</p>
				</div>

				<p>
					<b>DELETE</b> these accounts:
				</p>

				<form method="post" action="api/prune_temp">
				<table>
					<tr>
						<td style="padding-bottom: 20px;">
							<table id="memberlist">
								<tr>
							<?php
								$i = 0;

								// How many columns of members and how many per column?
								// We don't want more than about 5 columnns
								$numMems = count($mems);
								$sqrt = sqrt($numMems);
								$perCol = ceil($sqrt) > 5 ? ceil($numMems / 5) : ceil($sqrt);

								foreach ($mems as $mem):
									if ($mem->ID() == $MEMBER->ID())
										continue;
							?>
								<?php if ($i % $perCol == 0) echo '<td>'; ?>
									<label>
										<input type="checkbox" name="users[]" value="<?php echo $mem->ID(); ?>" class="standard">
										<?php echo $mem->FirstName(); ?> <?php echo $mem->LastName; ?>
									</label>
								<?php if ($i % $perCol == $perCol - 1) echo '</td>'; ?>
							<?php $i++; endforeach; ?>
								</tr>
							</table>
						</td>
					</tr>
				</table>

				<p class="text-center clr-red">Are you <i>sure</i> you've selected the right accounts?</p>


				<div class="text-center">
					<input type="submit" value="Delete Accounts">
					<br>
					<small><i class="clr-red">There is no going back!</i></small>
				</div>

				</form>

				<!-- END TEMPORARY -->

<?php
/*
	TODO: (in progress)

				<div class="instructions">
					<p>
						When ward membership changes so drastically between terms and semesters and the
						turnover rate is high, this page will help you delete the accounts of those
						who've moved out of the ward <i>en masse</i>.
					</p>

					<p>
						Start the process by selecting a "due date" and typing an optional message.
						All members of the ward will be sent an email and must click a link in that email
						by the "due date" to keep their accounts. The accounts of any members who did not click
						the link by that due date will automatically be deleted.
					</p>

					<p>
						After the process is started, you can see the progress of members' responses.
						You can also manually override the status of any members' response. Using this page,
						click on their name to determine the fate of their account.
					</p>
				</div>

				<p class="text-center">
					<i>No turnover process is currently running (no accounts pending deletion).</i>
				</p>
				
				<h2>Start the turnover process</h2>

				<form method="post" action="api/prunestart">
					<ul>
						<li>
							<b>
								Perform delete operation on &nbsp;
								<b style="color: red;">TODO -- MAKE THIS A FUNCTION/CONTROL</b>
								<select size="1" name="day" required="required">
									<option value="" selected="selected">(Day)</option>
								<?php
									for ($i = 1; $i <= 31; $i ++)
										echo "\t\t\t\t<option value=\"$i\">$i</option>\r\n";
								?>
								</select>
								<select size="1" name="month" required="required">
									<option value="" selected="selected">(Month)</option>
									<?php
										for ($i = 1; $i <= 12; $i ++)
											echo "\t\t\t\t<option value=\"$i\">".($months[$i - 1])."</option>\r\n";
									?>
								</select>
								<select size="1" name="year" required="required">
									<option value="" selected="selected">(Year)</option>
									<?php
										for ($i = date("Y"); $i < date("Y") + 2; $i ++)
											echo "\t\t\t\t<option value=\"$i\">$i</option>\r\n";
									?>
								</select>
								&nbsp;
								at <u>11:59 PM</u>.
							</b>
							<br>
							<small>(Members will have until this date and time to click the link and keep their accounts.)</small>
							<br><br>
						</li>
						<li>
							<b>Extra message:</b> <small>(optional)</small><br>
							<textarea rows="4" cols="50" name="msg" id="msg"></textarea>
							<br><br>
						</li>
						<li>
							<b>The email will look like this:</b><br>
							<div class="msgPreview">
								<b>Subject:</b> If you are staying in the ward...
								<br>
								<b>Message:</b>
								<p>
									If you are STAYING in the <?php echo $WARD->Name; ?> Ward next term/semester, please click
									this link to keep your account on the ward website:
								</p>
								<p>
									<a href="javascript:">https://<?php echo SITE_DOMAIN; ?>/ ...</a>
								</p>
								<p>
									If you are leaving the ward, there is nothing for you to do.
								</p>
								<div id="msgText"><!--populated with Javascript--></div>
								<p>
									Thank you!<br>
									-<?php 
										$fname = $MEMBER->FirstName();
										if ($fname == "Brother" || $fname == "Sister" || $fname == "Bishop")
											echo $fname . " " . $MEMBER->LastName;
										else
											echo $fname;
									?>
								</p>
							</div>
						</li>
					</ul>
					<br>

					<div class="text-center">
						<input type="submit" class="button" value="Start process &nbsp; &raquo;">
						<br><br>
						<small>You might want to make an announcement or
							<a href="../sms"><b>send out a text</b></a><br>
							to make sure all ward members who need to participate will do so.
						</small>
					</div>
				</form>
*/ ?>
			</div>

		</div>
		
		<?php include "../includes/footer.php"; ?>
		<?php include "../includes/nav.php"; ?>

<script>

$(function()
{
	// TODO/TEMPORARY: Until we're done with this page...
	$('form').submit(function(e)
	{	
		// TEMPORARY, until we are done with the checklist thing
		if (!confirm("This will delete " + $('input[type=checkbox]:checked').length + " accounts."))
			return suppress(e);
		else
			return true;

		// Use this behavior when the automatic feature is finished:
		/*if (!confirm("Are you sure? This will send an email to all members and require action on their part, or their accounts will be deleted."))
			return suppress(e);
		*/
	});

/*
	TODO (IN PROGRESS):

	// Message preview stuff...
	var preview = $('#msgText');
	var prevValue = preview.val();
	$('#msg').keyup(function()
	{
		var val = $(this).val();
		
		if (val != prevValue)
		{
			prevValue = val;
			val = val.replace(/(<([^>]+)>)/ig,"");		// Strip HTML
			val = val.replace(/\r/gi, "");				// Who needs \r anyway?
			val = val.replace(/\n\n/gi, "</p><p>");		// Make new paragraphs
			val = val.replace(/\n/gi, "<br>");			// Make line breaks
			preview.html("<p>" + val + "</p>");			// Wa-bam!
		}
	});
*/
});

</script>
	</body>
</html>