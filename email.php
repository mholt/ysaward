<?php
require_once("lib/init.php");
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
<html>
<head>
	<title>Email &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
	<?php include("includes/head.php"); ?>
<style>
#memberlist { font-size: 12px; }
table label { display: block; padding: 3px; cursor: pointer; }
table label:hover { background: #EEE; }
#memberlist td { white-space: nowrap; vertical-align: top; padding-right: 15px; }
.notdone {
	background: #CC0000;
	padding: 7px;
	color: #FFF;
	text-align: center;
	text-shadow: none;
	border-radius: 5px;
	margin: 20px 0 40px;
}
</style>
</head>
<body>
	
	<?php include("includes/header.php"); ?>
	
	<article class="grid-12 group">
		
		<section class="g-10 prefix-1 suffix-1">
			<h1>Email</h1>
			<!--
			<div class="notdone">
				Emailing isn't functional right now. We're working on it. Check back in a few days.
			</div>
			-->
			<p>
			<?php if ($has1): ?>
			You may email all the members of the ward.
			<?php elseif ($has2): ?>
			You may email all the brethren in the ward.
			<?php elseif ($has3): ?>
			You may email all the sisters in the ward.
			<?php else: ?>
			You may email up to <?php echo EMAIL_MAX_RECIPIENTS; ?> members of the ward at a time, or your FHE group.
			<br>To communicate with the whole ward, talk to a member of the publicity
			committee (or your ward's equivalent).
			<?php endif; ?>
			</p>

			<form method="post" action="api/startsendingemails.php">
			<table>
				<tr>
					<td style="min-width: 100px; padding-bottom: 20px;">
						<b>From:</b>
					</td>
					<td style="padding-bottom: 20px;">
						<?php echo $m->FirstName(); ?> <?php echo $m->LastName; ?> &lt;<?php echo EMAIL_FROM; ?>&gt;
					</td>
				</tr>
				<tr>
					<td style="min-width: 100px; padding-bottom: 20px; vertical-align: top;">
						<b>Reply-To:</b>
					</td>
					<td style="padding-bottom: 20px;">
						<?php echo $m->FirstName(); ?> <?php echo $m->LastName; ?> &lt;<?php echo $m->Email; ?>&gt;<br>
						<small><i>(Replies will be sent directly to you)</i></small>
					</td>
				</tr>
				<tr>
					<td style="vertical-align: top; padding-top: 7px;">
						<b>To:</b>
					</td>
					<td style="padding-bottom: 20px;">
						<?php if ($has1): ?>
						<label style="width: 220px;"><input type="checkbox" id="sel-all" class="sel"> <b>Select all</b></label>
						<?php endif; if ($has1 || $has2): ?>
						<label style="width: 220px;"><input type="checkbox" id="sel-bro" class="sel"> <b>Select all brothers</b></label>
						<?php endif; if ($has1 || $has3): ?>
						<label style="width: 220px;"><input type="checkbox" id="sel-sis" class="sel"> <b>Select all sisters</b></label>
						<?php endif; ?>
						<label style="width: 220px;"><input type="checkbox" id="sel-fhe" class="sel" name="fhe"> <b>Select my FHE group</b></label>

						
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
						?>
							<?php if ($i % $perCol == 0) echo '<td>'; ?>

								<label>
									<input type="checkbox" name="to[]" value="<?php echo $mem->ID(); ?>" data-gender="<?php echo $mem->Gender; ?>">
									<?php echo $mem->FirstName(); ?> <?php echo $mem->LastName; ?>
								</label>

							<?php if ($i % $perCol == $perCol - 1) echo '</td>'; ?>
						<?php $i++; endforeach; ?>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td style="vertical-align: top; padding-top: 8px;"><b>Subject:</b></td>
					<td style="padding-bottom: 20px;">
						<input type="text" name="subject" size="40" maxlength="255" required>
					</td>
				</tr>
				<tr>
					<td style="vertical-align: top;"><b>Message:</b></td>
					<td style="padding-bottom: 20px;">
						<textarea name="msg" cols="80" rows="9" required></textarea>
						<small><br><b>Privacy notice:</b> Email is not a secure form of communication.</small>
					</td>
				</tr>
				<tr>
					<td></td>
					<td style="padding-bottom: 20px; text-align: center;">
						<input type="submit" id="sub" value="Send &raquo;" class="button" style="width: 150px;">
						<img src="images/ajax-loader.gif" style="visibility: hidden; position: relative; top: 10px; left: 10px;" id="ajaxloader">
					</td>
				</tr>
			</table>
			</form>

		</section>
		
	</article>



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
				toastr.error("Please select at least one recipient.");
				return false;
			}
			if ($('input[name=subject]').val().length <= 4)
			{
				toastr.error("Please type a message subject longer than 4 characters.");
				return false;
			}
			if ($('textarea').val().length == 0)
			{
				toastr.error("You forgot to type a message...");
				return false;
			}
			if ($('textarea').val().length < 10)
			{
				toastr.warning("Please make your message a little bit longer. This will help make sure it doesn't go to spam boxes.");
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
					toastr.success("We also sent a copy of the message to your inbox.");

				if ($('input[type=checkbox]:checked').length > 10)
					toastr.success("Your emails have been queued up successfully and should be sent soon. Please allow several minutes to an hour for all emails to arrive.");
				else
					toastr.success("Your email was successfully queued up and should arrive in the next few minutes.");

				resetForm();
			}
			else
			{
				if (!xhr.responseText && xhr.status != 500)
				{
					toastr.info("Looks like things are a little slow right now, but your email(s) will be sent momentarily.");
					resetForm();
				}
				else
					toastr.error(xhr.responseText || "There was and your message could not be sent. Please report this.");
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
	$('#sel-all').click(function() {
		$('input[type=checkbox]').not('#sel-fhe').prop('checked', $(this).prop('checked'));
	});

	// Select brothers
	$('#sel-bro').click(function() {
		$('input[type=checkbox]').filter(function() {
			return $(this).data('gender') == <?php echo Gender::Male; ?>
		}).prop('checked', $(this).prop('checked'));
	});

	// Select sisters
	$('#sel-sis').click(function() {
		$('input[type=checkbox]').filter(function() {
			return $(this).data('gender') == <?php echo Gender::Female; ?>
		}).prop('checked', $(this).prop('checked'));
	});

	// Select FHE group
	$('#sel-fhe').click(function() {
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
		if ($('#memberlist input[type=checkbox]:checked').length > <?php echo EMAIL_MAX_RECIPIENTS; ?>)
		{
			$(this).prop('checked', false);
			if (!$(notifyToast).is(':visible'))
				notifyToast = toastr.info('You can only email up to <?php echo EMAIL_MAX_RECIPIENTS; ?> members at a time.');
		}
		<?php elseif ($has2): ?>
		// May email all brothers but not much more than that
		if ($('#memberlist input[type=checkbox]:checked').not(function() {
			return $(this).data('gender') == <?php echo Gender::Male; ?>;
		}).length > <?php echo EMAIL_MAX_RECIPIENTS; ?>)
		{
			$(this).prop('checked', false);
			if (!$(notifyToast).is(':visible'))
				notifyToast = toastr.info('You may email all the brothers, but beyond that you can only email up to <?php echo EMAIL_MAX_RECIPIENTS; ?> members at a time.');
		}
		<?php elseif ($has3): ?>
		// May email all sisters but not much more than that
		if ($('#memberlist input[type=checkbox]:checked').not(function() {
			return $(this).data('gender') == <?php echo Gender::Female; ?>;
		}).length > <?php echo EMAIL_MAX_RECIPIENTS; ?>)
		{
			$(this).prop('checked', false);
			if (!$(notifyToast).is(':visible'))
				notifyToast = toastr.info('You may email all the sisters, but beyond that you can only email up to <?php echo EMAIL_MAX_RECIPIENTS; ?> members at a time.');
		}
		<?php endif; ?>
	});
});
</script>
	
<?php include("includes/footer.php"); ?>