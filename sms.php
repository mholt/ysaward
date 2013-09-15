<?php
require_once "lib/init.php";
protectPage();

// TODO: Currently this page only supports regular members sending texts, not stake leaders.
// Ideally, stake leaders could choose which wards to send a text blast out to. The database should support this.

$canSendAll = $MEMBER->HasPrivilege(PRIV_TEXT_ALL);
$canSendFHE = $MEMBER->HasPrivilege(PRIV_TEXT_FHE);

// Get a list of all members of the ward
$mems = array();
$q = "SELECT ID FROM Members WHERE WardID='$MEMBER->WardID' ORDER BY FirstName,LastName ASC";
$r = DB::Run($q);
while ($row = mysql_fetch_array($r))
	array_push($mems, Member::Load($row['ID']));



// Get a list of this member's FHE group
$fheGroupMembers = array();
$r = DB::Run("SELECT ID FROM Members WHERE FheGroup='{$MEMBER->FheGroup}' AND FheGroup != ''");
while ($row = mysql_fetch_array($r))
	array_push($fheGroupMembers, $row['ID']);

$textsRemaining = SMS_MAX_PER_DAY - $MEMBER->TextMessagesSentInLastDay();

// Get current Nexmo balance
$request = new HttpRequest(SMS_META_BASE."/account/get-balance/".SMS_API_KEY."/".SMS_API_SECRET, HttpRequest::METH_GET);
$request->addHeaders(array("Accept" => "application/json"));
$request->send();
$response = json_decode($request->getResponseBody());
$minFundsRequired = 2;	// Minumum EUR on account to allow sending SMS
$notEnoughFunds = $response->value < $minFundsRequired && !$response->autoReload;	// The balance is in EUR
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Send text messages &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
		<?php include "includes/head.php"; ?>
		<style>
		/** NOTE: THESE STYLES ARE SAME AS EMAIL PAGE **/
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
		/** END STYLES SAME AS EMAIL PAGE **/

		.to label.disabled-always {
			color: #AAA;
			background: none;
			cursor: default;
		}
		#char-count {
			font-weight: bold;
		}
		.char-count-ok {
			color: #5588AA;
		}
		.char-count-close {
			color: #E3BD1C;
		}
		.char-count-warn {
			color: #CC0000;
		}
		.remaining-faded, .remaining-faded span {
			color: #CCC !important;
		}
		#message-parts {
			visibility: hidden;
			line-height: 1.5em;
		}
		#part-count {
			font-weight: bold;
			color: blue;
		}
		#cost {
			color: green;
		}

		.message-area {
			line-height: 1em;
		}
		</style>
	</head>
	<body>
		<?php include "includes/header.php"; ?>

		<form method="post" action="api/startsendingsms" class="narrow">
			
			<h1>Text messaging</h1>

<?php if ($notEnoughFunds): ?>
			<div class="text-center">
				<mark>
					<b>Text messaging is currently unavailable.</b>
				</mark>
				<br><br>
				Please ask your ward website administrator to add funds to the SMS account.
			</div>
<?php else:?>

			<fieldset class="to">
				<legend>To</legend>
				<div class="headers">
					<?php if ($canSendAll): ?>
					<label class="bold"><input type="checkbox" id="sel-all" class="sel standard"> Everyone</label>
					<label class="bold"><input type="checkbox" id="sel-bro" class="sel standard"> All brothers</label>
					<label class="bold"><input type="checkbox" id="sel-sis" class="sel standard"> All sisters</label>
					<?php endif; if ($canSendAll || $canSendFHE): ?>
					<label class="bold"><input type="checkbox" id="sel-fhe" class="sel standard" name="fhe"> My FHE group</label>
					<?php endif; ?>
					<div class="memberlist">
					<?php 
						foreach ($mems as $mem):	
							// Determine if we need to prevent this member from being selected
							// This could happen if they have a malformed number or opted out
							$shortNumber = strlen($mem->PhoneNumber) < 10;
							$disable = $shortNumber || !$mem->ReceiveTexts;
					?>
						<label<?php if ($disable) echo !$mem->ReceiveTexts ? ' title="Opted out of texts" class="disabled-always"' : ' title="Phone number too short or none provided" class="disabled-always"'; ?>>
							<input type="checkbox" name="to[]" value="<?php echo $mem->ID(); ?>" data-gender="<?php echo $mem->Gender; ?>" <?php echo $disable ? 'class="standard disabled-always" disabled' : 'class="standard"'; ?>>
							<?php echo $mem->FirstName(); ?> <?php echo $mem->LastName; ?>
						</label>
					<?php endforeach; ?>
					</div>
				</div>
			</fieldset>

			<fieldset class="message-area">
				<legend>Message</legend>

				<textarea name="msg" cols="40" rows="4" placeholder="Message body" maxlength="<?php echo SMS_CHARS_PER_TEXT * 4; ?>" required></textarea>
				
				<span class="float-right">Cost: <span id="cost">$0.00</span></span>
				<span id="char-remaining"><span id="char-count" class="char-count-ok"><?php echo SMS_CHARS_PER_TEXT; ?></span> remaining</span>

				<br>

				<div id="message-parts">Will be split into <span id="part-count">2</span> parts for each recipient</div>
				<br>
			</fieldset>

			<div class="text-center">
				<button type="submit">Send</button>
				<br>
				<br>
			</div>

<?php endif; ?>

		<?php include "includes/footer.php"; ?>
		<?php include "includes/nav.php"; ?>


<script>

// Default number of characters per message. With SMS in the United States, it's 160
var charsPerMessage = <?php echo SMS_CHARS_PER_TEXT; ?>;

// Messages too long to fit in one message need to be split. In order to ensure they arrive
// in the right order, 7 characters is used to indicate ordering. Once a message hits 160
// characters, each segment (including the first) is only 153 characters long. In other words,
// a 161-character-long message will have two segments: 1) length 153, and 2) length 8.
var segmentOverhead = <?php echo SMS_SEGMENT_OVERHEAD; ?>;


$(function()
{
	var notifyToast, segmentToast;
	var checkedBeforeFheChecked = [];
	var fheGroup = {<?php 
		foreach ($fheGroupMembers as $memID)
			echo "{$memID}: true, ";
		?> 0: false };

	var textsRemaining = <?php echo $textsRemaining; ?>;
	var tooManySegments = false;
	var segments = 1;

	// Submit form
	$('form').hijax({
		before: function()
		{
			var textarea = $('textarea');

			textarea.val(textarea.val().replace(/\r|\n/g, ' '));	// Line breaks are seen as "non-ASCII", so replace with a space instead.
			textarea.val(textarea.val().replace(/\s+|\t/g, ' '));	// Replace any extra whitespace

			if ($('input[type=checkbox]:checked').length < 1)
			{
				$.sticky("Please select at least one recipient.", { classList: 'error' });
				return false;
			}
			if (textarea.val().length == 0)
			{
				$.sticky("Please type a message.", { classList: 'error' });
				return false;
			}
			if (textarea.val().length < 3)
			{
				$.sticky("Please make your message at least 3 characters long.", { classList: 'error' });
				return false;
			}
			
			if (/[^ -~]/.test(textarea.val())) 	// THE MOST BEAUTIFUL REGULAR EXPRESSION EVER!!!
			{
				$.sticky("Fancy characters are not currently allowed. Please remove all special characters from your message.", { classList: 'error' });
				return false;
			}
			if (segments > 2)
			{
				$.sticky("That's a really long text. Please consider sending an email instead. If it's really urgent, send a shorter text inviting people to check their email.", { classList: 'error' });
				return false;
			}

			$('#sub').prop('disabled', true);
			$('#ajaxloader').css('visibility', 'visible');
		},
		complete: function(xhr)
		{
			var recipCount = $('input[type=checkbox]:checked').length;

			if (xhr.status == 200)
			{
				_gaq.push(['_trackEvent', 'Feature', 'SMS', 'Send']);

				if (recipCount > 10)
					$.sticky("Your texts are being sent! Please allow a few minutes for all messages to arrive.");
				else
					$.sticky("Your text is being sent! All messages should arrive in a few seconds.");
				resetForm(recipCount);
			}
			else
			{
				if (!xhr.responseText && xhr.status != 500)
				{
					$.sticky("Your texts are being sent! Please allow a few minutes for all messages to arrive.");
					resetForm(recipCount);
				}
				else
					$.sticky(xhr.responseText || "There was a problem and your message could not be sent. Please report this. Sorry.", { classList: 'error' });
			}

			$('#sub').prop('disabled', false);
			$('#ajaxloader').css('visibility', 'hidden');
		}
	});

	function resetForm(recipCount)
	{
		// Resets the form so they don't accidently send more texts

		if (recipCount)
		{	// Message was successfully sent; reset/update all the values too
			$('#message-parts').css('visibility', 'hidden');
			$('#char-remaining').removeClass('remaining-faded');
			$('#char-count').removeClass().addClass('char-count-ok').text(charsPerMessage);
			textsRemaining -= recipCount * segments;
			updateCost(0, 1);
			segments = 1;
			$('#texts-remaining').text(textsRemaining);
		}

		$('input[type=checkbox]').prop('checked', false).not('.disabled-always').prop('disabled', false);
		$('.to-fhe').remove();
		$('textarea').val('');
		$('input[type=text]').val('');
	}

	// Select all
	$('#sel-all').click(function() {
		$('input[type=checkbox]').not('#sel-fhe, :disabled').prop('checked', $(this).prop('checked'));
		updateCost($('.memberlist input[type=checkbox]:checked').length, segments);
	});

	// Select brothers
	$('#sel-bro').click(function() {
		$('input[type=checkbox]').filter(function() {
			return $(this).data('gender') == <?php echo Gender::Male; ?> && !$(this).is(':disabled');
		}).prop('checked', $(this).prop('checked'));
		updateCost($('.memberlist input[type=checkbox]:checked').length, segments);
	});

	// Select sisters
	$('#sel-sis').click(function() {
		$('input[type=checkbox]').filter(function() {
			return $(this).data('gender') == <?php echo Gender::Female; ?> && !$(this).is(':disabled');
		}).prop('checked', $(this).prop('checked'));
		updateCost($('.memberlist input[type=checkbox]:checked').length, segments);
	});

	// Select FHE group
	$('#sel-fhe').click(function()
	{
		if ($(this).is(':checked'))
		{
			// Save what's checked...
			checkedBeforeFheChecked = $('.memberlist input[type=checkbox]:checked')
				.prop('checked', false)
				.toArray();

			// Disable checkbox fields...
			$('input[type=checkbox]').not('#sel-fhe').prop('disabled', true);

			// Select the FHE group...
			$('.memberlist input[type=checkbox]').filter(function() {
				// Since disabled fields don't get sent to the server (apparently),
				// we need to inject some hidden input fields manually
				if (fheGroup[$(this).val()] && !$(this).hasClass('disabled-always'))
				{
					$('form').append('<input type="hidden" name="to[]" value="'+$(this).val()+'" class="to-fhe">');
					return true;
				}
			}).prop('checked', true);	
		}
		else
		{
			// Uncheck everything
			$('input[type=checkbox]').not('.disabled-always').prop('disabled', false).not('.sel').prop('checked', false);

			// Remove the FHE value "fix" from when we checked the box
			$('.to-fhe').remove();

			// Restore checked values
			$.each(checkedBeforeFheChecked, function(idx, elem) {
				$(this).prop('checked', true);
			});
		}

		updateCost($('.memberlist input[type=checkbox]:checked').length, segments);
	});


	// This block ensures a user can't select more than they're allowed to
	$('.memberlist input[type=checkbox]').change(function()
	{
		// Since the fields are disabled, this shouldn't be an issue, but just in case...
		if ($('#sel-fhe').is(':checked'))
			$('#sel-fhe').click();

		var recipCount = $('.memberlist input[type=checkbox]:checked').length;

		<?php if (!$canSendAll && !$canSendFHE): ?>
		// Default privileges
		if (recipCount * segments > textsRemaining)
		{
			$(this).prop('checked', false);
			if (!$(notifyToast).is(':visible'))
				notifyToast = $.sticky('You can send up to <?php echo SMS_MAX_PER_DAY; ?> texts every 24 hours, but you have only '+textsRemaining+' remaining.'+(segments > 1 ? ' With a message '+segments+' parts long, you cannot send to more recipients.' : ''));
			recipCount --;
		}
		<?php elseif (!$canSendAll && $canSendFHE): ?>
		// Can send to FHE group but not more than <?php echo MAX_PER_DAY ?>, otherwise
		if (recipCount * segments > <?php echo SMS_MAX_PER_DAY; ?> && !$('#sel-fhe').is(':checked'))
		{
			$(this).prop('checked', false);
			if (!$(notifyToast).is(':visible'))
				notifyToast = $.sticky('You can only send <?php echo SMS_MAX_PER_DAY; ?> text messages every 24 hours.');
			recipCount --;
		}
		<?php endif; ?>

		updateCost(recipCount, segments);
	});

	// Update the character count and price every keyup
	$('textarea').keyup(function()
	{
		var charCount = $('#char-count');
		var length = $(this).val().length;
		var remaining = <?php echo SMS_CHARS_PER_TEXT; ?> - length;
		var recipCount = $('.memberlist input[type=checkbox]:checked').length;


		$('#char-count').text(remaining);
		
		if (remaining <= 15)
			charCount.removeClass().addClass('char-count-warn');
		else if (remaining <= 35)
			charCount.removeClass().addClass('char-count-close');
		else if (!charCount.hasClass('char-count-ok'))
			charCount.removeClass().addClass('char-count-ok');

		if (length > <?php echo SMS_CHARS_PER_TEXT; ?>)
			segments = Math.ceil(length / (<?php echo SMS_CHARS_PER_TEXT; ?> - <?php echo SMS_SEGMENT_OVERHEAD; ?>));
		else
			segments = Math.ceil(length / <?php echo SMS_CHARS_PER_TEXT; ?>);
		
		if (segments > 1)
		{
			$('#char-remaining').removeClass().addClass('remaining-faded');
			$('#message-parts').css('visibility', 'visible');
			$('#part-count').text(segments);

			<?php if (!$canSendAll): ?>
			if (segments * recipCount > textsRemaining && !$('#sel-fhe').is(':checked'))
			{
				if (!$(segmentToast).is(':visible'))
				segmentToast = toastr.warning("Your message is too long for the number of recipients you've selected. Please make it shorter or choose less recipients.");
				$('#sub').prop('disabled', true);
				tooManySegments = true;
			}
			<?php endif; ?>
		}
		else
		{
			$('#message-parts').css('visibility', 'hidden');
			$('#char-remaining').removeClass('remaining-faded');

			if (tooManySegments)
			{
				$('#sub').prop('disabled', false);
				tooManySegments = false;
			}
		}

		updateCost(recipCount, segments);
	});

	function updateCost(recipients, segments)
	{
		var cost = recipients * (segments || 1) * 0.01;	// About 1 US cent to send a text message (segment) to a recipient
		$('#cost').text("$" + parseFloat(Math.round(cost * 100) / 100).toFixed(2));
		return cost;
	}
});
</script>
	</body>
</html>