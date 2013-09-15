<?php
require_once "lib/init.php";

// Springboard if logged in
if (Member::IsLoggedIn())
	header("Location: /directory.php");

// If user hasn't entered a ward password yet, prompt for it.
// This keeps the creepers out.
if (!isset($_SESSION['ward_id'])):
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Register &mdash; <?php echo SITE_NAME; ?></title>
		<?php include "includes/head.php"; ?>
	</head>
	<body class="narrow">

			<form method="post" action="api/wardpwd.php">
				<div class="text-center">
					<a href="/">
						<img src="<?php echo SITE_LARGE_IMG; ?>" alt="<?php echo SITE_NAME; ?>" class="logo-big">
					</a>

					<h1>Register</h1>

					<hr>

					<?php require "includes/controls/wardpicker.php"; ?>

					<input type="password" name="pwd" placeholder="Ward password" required>
				</div>

				<div class="text-right">
					<button type="submit">Continue</button><br>
					<br>
				</div>
			</form>

			<?php include "includes/footer.php"; ?>

<script>
$(function()
{
	$('select').prop('required', true).focus();

	$('select').change(function()
	{
		$('input[type=password]').focus();
	});
	
	$('form').ajaxForm({
		complete: function(xhr)
		{
			if (xhr.status == 200)
				// Correct ward/password combination; proceed to registration form.
				window.location = '/register';
			else
				$.sticky(xhr.responseText || "There was a problem. (Maybe the Internet is really slow right now?) Please try again, or check your connection.", { classList: "error" });
		}
	});
});
</script>
	</body>
</html>
<?php
else:
	// User has chosen his/her ward and typed the correct password.
	$wid = $_SESSION['ward_id'];
	$ward = Ward::Load($wid);

	if ($ward->Deleted)
		fail("Sorry, for whatever reason, that ward is no longer available on this site.");

	// Get a list of residences in the ward so the user can conveniently pick one
	$residences = $ward->Residences();
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Register &mdash; <?php echo SITE_NAME; ?></title>
		<?php include "includes/head.php"; ?>
	</head>
	<body class="narrow">

		<form method="post" action="/api/register.php">
			<div class="text-center">
				<a href="/">
					<img src="<?php echo SITE_LARGE_IMG; ?>" alt="<?php echo SITE_NAME; ?>" class="logo-big">
				</a>

				<h1>Register</h1>

				<mark>
					<b>Ward:</b> <?php echo $ward->Name; ?>
				</mark>

				<hr>

				<input type="email" name="email" placeholder="Email address" required>
				<input type="password" name="pwd1" placeholder="Password" required>
				<input type="password" name="pwd2" placeholder="Password again" required>
				
				<br>

				<input type="text" name="fname" placeholder="First name" required>
				<input type="text" name="mname" placeholder="Middle name or initial">
				<input type="text" name="lname" placeholder="Last name" required>

				<hr>

				<input type="radio" name="gender" data-label="I'm a guy" value="<?php echo Gender::Male; ?>">
				&nbsp;
				<input type="radio" name="gender" data-label="I'm a girl" value="<?php echo Gender::Female; ?>"9>
				
				<hr>

				<div class="text-left" style="line-height: 18px;">
					<hr class="line">
					
					<b>Profile picture:</b>
					
					<br><br>

					<small class="clr-red">
						<i>
							<b>This is very important!</b><br>
							Please choose a picture with just you in it.<br>
							Max size: 5 MB
						</i>
					</small>

					<br><br>

					<input type="file" name="profilepic" accept="image/jpeg" style="font-size: 16px;">
					
					<hr class="line">
				</div>

				<hr>

				<!-- HOUSING -->
				<select size="1" name="resID" id="bldg" required>
					<option value="" selected>Housing...</option>
					
					<?php foreach ($residences as $residence): ?>
					<option value="<?php echo $residence->ID(); ?>"><?php echo $residence->Name; if (!$residence->NameUnique()) echo ' ('.$residence->Address.')'; ?></option>
					<?php endforeach; ?>
					
					<option value="-">(Other)</option>
				</select>
				<span id="usualapt" style="display: none;">
					<input type="text" name="aptnum" maxlength="4" placeholder="Unit #">
				</span>
				<span id="otherapt" style="display: none;">
					<input type="text" id="address" placeholder="Your full address" name="address" size="45" maxlength="255">
				</span>
				<input type="hidden" name="streetAddress" id="streetAddress" value="">
				<input type="hidden" name="city" id="city" value="">
				<input type="hidden" name="state" id="state" value="">
				<input type="hidden" name="zipcode" id="zipcode" value="">
				<!-- END HOUSING -->

				<hr>

				<input type="text" name="dob" id="dob" placeholder="Date of birth" required>
				<input type="tel" name="phone" placeholder="Phone number">
				
				<hr>

				<div class="text-left">
					<input type="checkbox" data-label="Keep birth year private" checked disabled>
					<input type="checkbox" name="hideBirthday" data-label="Keep birth month and day private">
					<input type="checkbox" name="hidePhone" data-label="Keep phone number private">
					<input type="checkbox" name="hideEmail" data-label="Keep email address private">
				</div>

				<hr>

			</div>

			<div class="text-right">
				<button type="submit">Continue</button><br>
				<br>
			</div>
		</form>

		<?php include "includes/footer.php"; ?>

<script src="//d79i1fxsrar4t.cloudfront.net/jquery.liveaddress/2.4/jquery.liveaddress.min.js"></script>
<script>
$(function()
{
	// Stores a custom address in case the user hides then displays it again (cross-browserness)
	var addrCache = $('#address').val();

	$('input[type=radio], input[type=checkbox]').prettyCheckable();

	$('input').first().focus();

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

	$('form').ajaxForm({
		complete: function(xhr)
		{
			if (xhr.status == 200)
			{
				// They've been logged in, so take them to the answers page
				window.location = '/survey?new';
			}
			else
				$.sticky(xhr.responseText || "Something's not quite right. Check your Internet connection, try again, then open a new tab and try logging in.", { classList: "error", autoclose: 7500 });
		}
	});

});


var liveaddress = $.LiveAddress({
	key: "<?php echo SMARTYSTREETS_HTML_KEY; ?>",
	autoMap: false
});

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
<?php endif; ?>