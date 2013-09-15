<?php
require_once "lib/init.php";
protectPage();

$m = Member::Current();

// Get a list of all members of the ward
$mems = array();

$q = "SELECT ID FROM Members WHERE WardID='$MEMBER->WardID' ORDER BY FirstName,LastName ASC";
$r = DB::Run($q);

while ($row = mysql_fetch_array($r))
	array_push($mems, Member::Load($row['ID']));

// Get member's privileges in these matters
$has1 = $m->HasPrivilege(PRIV_EMAIL_ALL);	// Email all members
$has2 = $m->HasPrivilege(PRIV_EMAIL_BRO);	// Email all brethren
$has3 = $m->HasPrivilege(PRIV_EMAIL_SIS);	// Email all sisters


// Get a list of this member's FHE group for convenience
$fheGroupMembers = array();
$r = DB::Run("SELECT ID FROM Members WHERE FheGroup='{$MEMBER->FheGroup}' AND FheGroup != ''");
while ($row = mysql_fetch_array($r))
	array_push($fheGroupMembers, $row['ID']);
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Email &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
		<?php include "includes/head.php"; ?>
		<style>
		fieldset legend {
			margin-bottom: 1em;
		}

		.headers {
			font-size: 14px;
			margin-bottom: 2em;
		}

		.memberlist {
			overflow-y: scroll;
			height: 300px;
			display: inline-block;
			min-width: 200px;
			background: #FFF;
			border-radius: 10px;
			margin-top: 1em;
			border: 1px solid #AAA;
		}

		.to label {
			display: block;
			cursor: pointer;
			padding: 0 5px;
		}

		.to label.bold {
			width: 150px;
			border-radius: 5px;
		}

		.to label:hover {
			background: #EEE;
		}
		</style>
	</head>
	<body>
		<?php include "includes/header.php"; ?>

		<form method="post" action="api/startsendingemails" class="narrow">

			<div class="text-center">
				<h1>Send email</h1>
			</div>

			<fieldset>
				<legend>From</legend>
				<div class="headers">
					<b><?php echo $m->FirstName(); ?> <?php echo $m->LastName; ?></b> &lt;<?php echo EMAIL_FROM; ?>&gt;
				</div>
			</fieldset>


			<fieldset>
				<legend>Reply-To</legend>
				<div class="headers" style="line-height: 1.5em;">
					<b><?php echo $m->FirstName(); ?> <?php echo $m->LastName; ?></b> &lt;<?php echo $m->Email; ?>&gt;
					<br>
					<small><i style="font-weight: 300;">(Replies will be sent directly to you)</i></small>
				</div>
			</fieldset>

			<fieldset class="to">
				<legend>To</legend>
				<div class="headers">
					<?php if ($has1): ?>
					<label class="bold"><input type="checkbox" id="sel-all" class="sel standard"> Everyone</label>
					<?php endif; if ($has1 || $has2): ?>
					<label class="bold"><input type="checkbox" id="sel-bro" class="sel standard"> All brothers</label>
					<?php endif; if ($has1 || $has3): ?>
					<label class="bold"><input type="checkbox" id="sel-sis" class="sel standard"> All sisters</label>
					<?php endif; ?>
					<label class="bold"><input type="checkbox" id="sel-fhe" class="sel standard" name="fhe"> My FHE group</label>
					<div class="memberlist">
					<?php foreach ($mems as $mem): ?>
						<label>
							<input type="checkbox" name="to[]" value="<?php echo $mem->ID(); ?>" data-gender="<?php echo $mem->Gender; ?>" class="standard">
							<?php echo $mem->FirstName(); ?> <?php echo $mem->LastName; ?>
						</label>
					<?php endforeach; ?>
					</div>
				</div>
			</fieldset>


			<fieldset>
				<legend>Message</legend>
				<br>
				<input type="text" name="subject" maxlength="255" placeholder="Subject" required>
				<br>
				<textarea name="msg" rows="5" placeholder="Body" required></textarea>
				<small><b>Note:</b> Email is not a secure form of communication.</small>
			</fieldset>
			<hr>

			<div class="text-center">
				<button type="submit">Send</button>
				<br>
				<br>
			</div>
		</form>

		<?php include "includes/footer.php"; ?>
		<?php include "includes/nav.php"; ?>

<script type="text/javascript">
$(function() {
	var notifyToast;
	var checkedBeforeFheChecked = [];
	var fheGroup = {<?php 
		foreach ($fheGroupMembers as $memID)
			echo "{$memID}: true, ";
		?> 0: false };


	// Submit form
	$('form').hijax({
		before: function() {
			if ($('input[type=checkbox]:checked').length < 1)
			{
				$.sticky("Please select at least one recipient.", { classList: 'error' });
				return false;
			}
			if ($('input[name=subject]').val().length <= 4)
			{
				$.sticky("Please type a message subject longer than 4 characters.", { classList: 'error' });
				return false;
			}
			if ($('textarea').val().length == 0)
			{
				$.sticky("You forgot to type a message...", { classList: 'error' });
				return false;
			}
			if ($('textarea').val().length < 10)
			{
				$.sticky("Please make your message a little bit longer. This will help make sure it doesn't go to spam boxes.", { classList: 'error' });
				return false;
			}

			$('#sub').prop('disabled', true);
			$('#ajaxloader').css('visibility', 'visible');
		},
		complete: function(xhr) {
			if (xhr.status == 200)
			{
				_gaq.push(['_trackEvent', 'Feature', 'Email', 'Send']);

				if (xhr.responseText == "1")
					$.sticky("We also sent a copy of the message to your inbox.");

				if ($('input[type=checkbox]:checked').length > 10)
					$.sticky("Your emails have been queued up successfully and should be sent soon. Please allow several minutes to an hour for all emails to arrive.");
				else
					$.sticky("Your email was successfully queued up and should arrive in the next few minutes.");

				resetForm();
			}
			else
			{
				if (!xhr.responseText && xhr.status != 500)
				{
					$.sticky("Looks like things are a little slow right now, but your email(s) will be sent momentarily.");
					resetForm();
				}
				else
					$.sticky(xhr.responseText || "There was and your message could not be sent. Please report this.", { classList: 'error' });
			}

			$('#sub').prop('disabled', false);
			$('#ajaxloader').css('visibility', 'hidden');
		}
	});

	function resetForm()
	{
		// Resets the form so they don't accidently send more emails
		$('input[type=checkbox]').prop('checked', false);
		$('.to-fhe').remove();
		$('textarea').val('');
		$('input[type=text]').val('');
	}

	// Select all
	$('#sel-all').change(function() {
		$('input[type=checkbox]').not('#sel-fhe').prop('checked', $(this).prop('checked'));
	});

	// Select brothers
	$('#sel-bro').change(function() {
		$('input[type=checkbox]').filter(function() {
			return $(this).data('gender') == <?php echo Gender::Male; ?>
		}).prop('checked', $(this).prop('checked'));
	});

	// Select sisters
	$('#sel-sis').change(function() {
		$('input[type=checkbox]').filter(function() {
			return $(this).data('gender') == <?php echo Gender::Female; ?>
		}).prop('checked', $(this).prop('checked'));
	});

	// Select FHE group
	$('#sel-fhe').change(function() {
		if ($(this).is(':checked'))
		{
			// Save what's checked...
			checkedBeforeFheChecked = $('input[type=checkbox]:checked')
				.not('.sel')
				.prop('checked', false)
				.toArray();

			// Disable checkbox fields...
			$('input[type=checkbox]').not('#sel-fhe').prop('disabled', $(this).is(':checked'));

			// Select the FHE group...
			$('input[type=checkbox]').filter(function() {
				var isFHE = fheGroup[$(this).val()];

				// Since disabled fields don't get sent to the server (apparently),
				// we need to inject some input fields manually
				if (isFHE)
					$('form').append('<input type="hidden" name="to[]" value="'+$(this).val()+'" class="to-fhe">');

				return isFHE;
			}).prop('checked', $(this).prop('checked'));			
		}
		else
		{
			// Uncheck everything
			$('input[type=checkbox]').prop('disabled', false).not('.sel').prop('checked', false);

			// Remove the FHE value "fix" from when we checked the box
			$('.to-fhe').remove();

			// Restore checked values
			$.each(checkedBeforeFheChecked, function(idx, elem) {
				$(this).prop('checked', true);
			});
		}
	});


	// This block ensures a user can't select more than they're allowed to
	$('#memberlist input[type=checkbox]').change(function() {

		// Since the fields are disabled, this shouldn't be an issue, but just in case...
		if ($('#sel-fhe').is(':checked'))
			$('#sel-fhe').click();

		<?php if (!$has1 && !$has2 && !$has3): ?>
		if ($('.memberlist input[type=checkbox]:checked').length > <?php echo EMAIL_MAX_RECIPIENTS; ?>)
		{
			$(this).prop('checked', false);
			if (!$(notifyToast).is(':visible'))
				notifyToast = $.sticky('You can only email up to <?php echo EMAIL_MAX_RECIPIENTS; ?> members at a time.');
		}
		<?php elseif ($has2): ?>
		// May email all brothers but not much more than that
		if ($('.memberlist input[type=checkbox]:checked').not(function() {
			return $(this).data('gender') == <?php echo Gender::Male; ?>;
		}).length > <?php echo EMAIL_MAX_RECIPIENTS; ?>)
		{
			$(this).prop('checked', false);
			if (!$(notifyToast).is(':visible'))
				notifyToast = $.sticky('You may email all the brothers, but beyond that you can only email up to <?php echo EMAIL_MAX_RECIPIENTS; ?> members at a time.');
		}
		<?php elseif ($has3): ?>
		// May email all sisters but not much more than that
		if ($('.memberlist input[type=checkbox]:checked').not(function() {
			return $(this).data('gender') == <?php echo Gender::Female; ?>;
		}).length > <?php echo EMAIL_MAX_RECIPIENTS; ?>)
		{
			$(this).prop('checked', false);
			if (!$(notifyToast).is(':visible'))
				notifyToast = $.sticky('You may email all the sisters, but beyond that you can only email up to <?php echo EMAIL_MAX_RECIPIENTS; ?> members at a time.');
		}
		<?php endif; ?>
	});
});
</script>

	</body>
</html>