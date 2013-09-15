<?php
require_once("lib/init.php");
protectPage();


// Parse the apartment/address for display
$unit = $MEMBER->Apartment;


// Get parts of the birth date
$date = date('F d, Y', strtotime($MEMBER->Birthday));

// Build month list
$months = array("January", "February", "March", "April",
	"May", "June", "July", "August", "September", "October",
	"November",	"December");

// Profile picture filename
$profilePic = $MEMBER->PictureFile();

// Get a list of residences in the ward so the user can conveniently pick one
$residences = $WARD->Residences();

// Get the member's current residence
$currentResidence = $MEMBER->Residence();
$isCustom = $currentResidence ? $currentResidence->Custom() : false;

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Edit profile &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
		<?php include "includes/head.php"; ?>
		<style>
		#chgward {
			display: none;
		}

		select {
			margin-bottom: .5em;
		}

		#ppic {
			max-width: <?php echo Member::MEDIUM_DIM / 2; ?>px;
			max-height: <?php echo Member::MEDIUM_DIM / 2; ?>px;
		}

		#pic {
			line-height: 1em;
		}
		</style>
	</head>
	<body>
			<?php include "includes/header.php"; ?>

			<form method="post" action="/api/saveprofile" class="narrow">
				<div class="text-center">
					<h1>Edit profile</h1>
				</div>


					<fieldset>
						<legend>
							Email and password
						</legend>
						<input type="email" name="email" placeholder="Email address" maxlength="255" value="<?php echo htmlentities($MEMBER->Email); ?>" required>
						<input type="password" name="pwd1" placeholder="New password">
						<input type="password" name="pwd2" placeholder="New password again">
					</fieldset>

					<br>

					<fieldset>
						<legend>
							Personal
						</legend>

						<input type="text" name="fname" placeholder="First name" maxlength="45" value="<?php echo htmlentities($MEMBER->FirstName); ?>" required>
						<input type="text" name="mname" placeholder="Middle name or initial" maxlength="45" value="<?php echo htmlentities($MEMBER->MiddleName); ?>">
						<input type="text" name="lname" placeholder="Last name" maxlength="45" value="<?php echo htmlentities($MEMBER->LastName); ?>" required>

						<hr>

						<input type="text" name="dob" id="dob" placeholder="Date of birth" maxlength="100" value="<?php echo $date; ?>" required>
						<input type="tel" name="phone" placeholder="Phone number" maxlength="20" value="<?php echo htmlentities($MEMBER->PhoneNumber); ?>">

						<hr>

						<div class="text-center">
							<input type="radio" name="gender" data-label="I'm a guy" value="<?php echo Gender::Male; ?>"<?php echo $MEMBER->Gender == Gender::Male ? ' checked' : ''; ?>>
							&nbsp;
							<input type="radio" name="gender" data-label="I'm a girl" value="<?php echo Gender::Female; ?>"<?php echo $MEMBER->Gender == Gender::Female ? ' checked' : ''; ?>>
						</div>
					</fieldset>

					<br>

					<fieldset>
						<legend>
							Where you live
						</legend>

						<!-- HOUSING -->
						<select size="1" name="resID" id="bldg" required>
							<option value=""<?php if (!$currentResidence) echo ' selected'; ?>>Housing...</option>
							
							<?php foreach ($residences as $residence): ?>
							<option value="<?php echo $residence->ID(); ?>"<?php if ($currentResidence && $currentResidence->ID() == $residence->ID()) echo ' selected'; ?>><?php echo $residence->Name; if (!$residence->NameUnique()) echo ' ('.$residence->Address.')'; ?></option>
							<?php endforeach; ?>
							
							<option value="-"<?php if ($isCustom) echo ' selected'; ?>>(Other)</option>
						</select>
						<span id="usualapt"<?php if ($isCustom) echo ' style="display: none;"'; ?>>
							<input type="text" name="aptnum" maxlength="4" placeholder="Unit #"<?php if ($unit) echo ' value="'.htmlentities($unit).'"'; ?>>
						</span>
						<span id="otherapt"<?php if (!$isCustom) echo ' style="display: none;"'; ?>>
							<input type="text" id="address" placeholder="Your full address" name="address" size="45" maxlength="255"<?php if ($isCustom) echo ' value="'.htmlentities($currentResidence->String()).'"'; ?>>
						</span>
						<input type="hidden" name="streetAddress" id="streetAddress" value="">
						<input type="hidden" name="city" id="city" value="">
						<input type="hidden" name="state" id="state" value="">
						<input type="hidden" name="zipcode" id="zipcode" value="">
						<!-- END HOUSING -->
					</fieldset>

					<br>

					<fieldset>
						<legend>
							<input type="checkbox" data-label="Change ward" id="change-ward">
						</legend>
						<div id="chgward">
							<?php require "includes/controls/wardpicker.php"; ?>
							<input type="password" name="wardpwd" id="wardpwd" placeholder="Ward password">
						</div>
					</fieldset>
					<script>
					// TODO: Move this in permanently with the JS on this page when finished...
					$(function() {
						$('#wardid').val('');
						$('#change-ward').change(function() {
							if ($(this).is(':checked'))
								$('#chgward').slideDown().find('input, select').prop('required', true);
							else
								$('#chgward').slideUp().find('input, select').prop('required', false);
						});
					});
					</script>

					<hr>


					<fieldset id="pic">
						<legend>
							Current profile picture
						</legend>
						
						<img src="<?php echo $profilePic; ?>" id="ppic">
						<hr>
						<legend style="margin-bottom: 1em;">
							Change picture
						</legend>

						<small>
							<i>
								<b>This is very important!</b>
								<br>
								Please choose a picture with just you in it,
								<br>
								then submit the form to save your new picture.
								<br>
								(Max size: 5 MB)
							</i>
						</small>
						
						<br><br>

						<input type="file" name="profilepic" id="profilepic" accept="image/jpeg" style="font-size: 16px;">
					</fieldset>

					<br>


					<fieldset>
						<legend>
							Privacy
						</legend>

						<div class="text-left">
							<input type="checkbox" data-label="Keep birth year private" checked disabled>
							<input type="checkbox" name="hideBirthday" data-label="Keep birth month and day private"<?php if ($MEMBER->HideBirthday) echo ' checked'; ?>>
							<input type="checkbox" name="hidePhone" data-label="Keep phone number private"<?php if ($MEMBER->HidePhone) echo ' checked'; ?>>
							<input type="checkbox" name="hideEmail" data-label="Keep email address private"<?php if ($MEMBER->HideEmail) echo ' checked'; ?>>
							<br><br>
							<input type="checkbox" name="receiveSms" data-label="Receive occasional ward-related texts"<?php if ($MEMBER->ReceiveTexts) echo ' checked'; ?>>
						</div>
					</fieldset>

					<hr>

				<div class="text-center">
					<button type="submit">Save</button>
					<br>
					<br>
				</div>
			</form>

			<?php include "includes/footer.php"; ?>

		

			<?php include "includes/nav.php"; ?>

<script src="//d79i1fxsrar4t.cloudfront.net/jquery.liveaddress/2.4/jquery.liveaddress.min.js"></script>
<script>
$(function()
{
	// Stores a custom address in case the user hides then displays it again (cross-browserness)
	var addrCache = $('#address').val();

	// Functionality for changing wards (ward picker)
	$('#wardid').val('');
	$('#change-ward').change(function() {
		if ($(this).is(':checked'))
			$('#chgward').slideDown().find('input, select').prop('required', true);
		else
			$('#chgward').slideUp().find('input, select').prop('required', false);
	});

	// Verify date of birth is a good date format
	$('#dob').change(function()
	{
		$.get('/api/tryparsedate.php', {
			input: $(this).val(),
			strictMonthDayYear: '1'
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

	
	$('#bldg').change(function()
	{
		if ($(this).val() == "-")
		{
			// Show the "full address" field
			$('#usualapt input').prop('required', false);
			$('#address').val(addrCache);
			$('#usualapt').hide();
			$('#otherapt').show();
			$('#otherapt input').prop('required', true).focus();
			liveaddress.mapFields('auto');
		}
		else if ($(this).val() != "")
		{
			// Show the "unit number" field
			$('#otherapt input').prop('required', false);
			$('#otherapt').hide();
			addrCache = $('#address').val();
			$('#address').val();
			$('#usualapt').show();
			$('#usualapt input').prop('required', true).focus();
			liveaddress.deactivate();
		}
		else
		{
			$('#usualapt input, #otherapt input').prop('required', false);
			$('#usualapt, #otherapt').hide();
			liveaddress.deactivate();
		}
	});



	// ajaxForm from: http://malsup.com/jquery/form/progress.html
	// (Also on GitHub; documentation is lame: https://github.com/malsup/form)
	// The code below sup(*love*ports a progress bar which I haven't bothered
	// to implement yet.
	// We use this plugin instead of hijax because hijax doesn't support
	// file uploads...
	//var bar = $('.bar');
	//var percent = $('.percent');
	//var status = $('#status');

	var hasPic = false;

	$('form').ajaxForm({
		beforeSend: function(formData, jqForm, options)
		{
			//var queryString = $.param(formData); // Not needed, just an example. -- actually, this and the arguments go for "beforeSubmit" callback
			//status.empty();
			//var percentVal = '0%';
			//bar.width(percentVal)
			//percent.html(percentVal);
			hasPic = $('#profilepic').val().length > 0;

			$('[type=submit]').showSpinner();
		},
		uploadProgress: function(event, position, total, percentComplete)
		{
			// Also not needed, but here in case we implement it
			//var percentVal = percentComplete + '%';
			//bar.width(percentVal)
			//percent.html(percentVal);
		},
		complete: function(jqxhr)
		{
			if (jqxhr.status == 200)
			{
				_gaq.push(['_trackEvent', 'Account', 'Submit Form', 'Edit Profile']);

				if ($('#wardpwd').is(':visible'))
				{
					$.sticky("Redirecting...");
					setTimeout(function() { window.location = "/answers?new"; }, 1500);
				}
				
				$.sticky(jqxhr.responseText || "Saved your profile!");

				if (hasPic)
				{
					$.hijax({
						url: '/api/currentmemberpicturepath',
						complete: function(jqxhr) {
							$('#ppic').show().attr('src', jqxhr.responseText);
							hasPic = false;
						}
					});
				}
			}
			else
				$.sticky(jqxhr.responseText || "There might have been a problem. Please check your Internet connection and try again.", { classList: "error" });

			$('[type=submit]').hideSpinner();
		}
	});

});


var liveaddress = $.LiveAddress({
	key: "<?php echo SMARTYSTREETS_HTML_KEY; ?>",
	autoMap: false
});

<?php if ($isCustom): ?>
	$('#usualapt input').removeAttr('required');
	liveaddress.mapFields("auto");
<?php endif; ?>


liveaddress.on("AddressAccepted", function(event, data, previousHandler)
{
	if (data.response.isMissingSecondary())
	{
		data.address.abort(event);
		alert("Don't forget your apartment number!");
	}
	else
	{
		if (data.response.chosen)
			fillOutAddressFields(data.response.chosen);
		previousHandler(event, data);
	}
});

function fillOutAddressFields(responseAddr)
{
	$('#streetAddress').val(responseAddr.delivery_line_1);
	$('#city').val(responseAddr.components.city_name);
	$('#state').val(responseAddr.components.state_abbreviation);
	$('#zipcode').val(responseAddr.components.zipcode);	// 5-digit only; this is on purpose
}
</script>
	</body>
</html>