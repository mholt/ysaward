<?php
require_once("lib/init.php");
protectPage();


// Parse the apartment/address for display
$unit = $MEMBER->Apartment;


// Get parts of the birth date
$date = strtotime($MEMBER->Birthday);
$mm = date("m", $date);
$dd = date("j", $date);
$yyyy = date("Y", $date);

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
<html>
<head>
	<title>Edit profile &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
	<?php include("includes/head.php"); ?>
</head>
<body>
	
	<?php include("includes/header.php"); ?>
	
	<article class="grid-12 group">
		
		<section class="g-12">
			<div class="text-center">
				<h1>Edit profile</h1>
				<p><b>Switch to:</b> <a href="answers.php">Edit survey answers</a><!-- or <a href="member.php?id=<?php echo $MEMBER->ID(); ?>">view profile</a></p> -->
			</div>
			
			<p style="font-style: italic;">Fields marked with <span class="req">*</span> are required.</p>

			<form method="post" action="api/saveprofile.php" enctype="multipart/form-data">
				<table class="formTable">
					<tr>
						<td colspan="3">
							<hr>
							<h4>About you</h4>
						</td>
					</tr>
					<tr style="background: #EEE;">
						<td class="reqtd"><span class="req">*</span></td>
						<td>
							First name
						</td>
						<td>
							<input type="text" size="25" maxlength="45" name="fname" value="<?php echo htmlentities($MEMBER->FirstName); ?>" required="required">
						</td>
					</tr>
					<tr>
						<td></td>
						<td>
							Middle name (or initial)
						</td>
						<td>
							<input type="text" size="25" maxlength="45" name="mname" value="<?php echo htmlentities($MEMBER->MiddleName); ?>">
						</td>
					</tr>
					<tr style="background: #EEE;">
						<td class="reqtd"><span class="req">*</span></td>
						<td>
							Last name
						</td>
						<td>
							<input type="text" size="25" maxlength="45" name="lname" value="<?php echo htmlentities($MEMBER->LastName); ?>" required="required">
						</td>
					</tr>
					<tr>
						<td class="reqtd"><span class="req">*</span></td>
						<td>
							Birth date
						</td>
						<td>
							<select size="1" name="day" required="required">
								<option value=""></option>
		<?php
			for ($i = 1; $i <= 31; $i ++)
			{
				echo "\t\t\t\t\t\t<option value=\"$i\"";
				if ($i == $dd) echo ' selected="selected"';
				echo ">$i</option>\r\n";
			}
		?>
							</select>
							<select size="1" name="month" required="required">
								<option value=""></option>
		<?php
			for ($i = 1; $i <= 12; $i ++)
			{
				echo "\t\t\t\t\t\t<option value=\"$i\"";
				if ($i == $mm) echo 'selected="selected"';
				echo ">{$months[$i - 1]}</option>\r\n";
			}
		?>
							</select>
							<select size="1" name="year" required="required">
								<option value=""></option>
		<?php
			for ($i = date("Y") - 15; $i >= 1940; $i --)
			{
				echo "\t\t\t\t\t\t<option value=\"$i\"";
				if ($i == $yyyy) echo ' selected="selected"';
				echo ">$i</option>\r\n";
			}
		?>
							</select>
							<small><i>(Year isn't displayed)</i></small>
							<br><input type="checkbox"<?php if ($MEMBER->HideBirthday) echo ' checked="checked"'; ?> value="1" name="hideBirthday" id="hideBirthday"><label for="hideBirthday"> Private <i>(hide the whole thing)</i></label>
						</td>
					</tr>
					<tr style="background: #EEE;">
						<td class="reqtd"><span class="req">*</span></td>
						<td>
							Gender
						</td>
						<td>
						<input type="radio" name="gender" value="<?php echo Gender::Male; ?>" id="male" required="required"<?php echo $MEMBER->Gender == Gender::Male ? ' checked="checked"' : ''; ?>><label for="male"> Male</label><br>
							<input type="radio" name="gender" value="<?php echo Gender::Female; ?>" id="female" required="required"<?php echo $MEMBER->Gender == Gender::Female ? ' checked="checked"' : ''; ?>><label for="female"> Female</label>
						</td>
					</tr>
					<tr>
						<td class="reqtd"><span class="req">*</span></td>
						<td>
							Apartment
						</td>
						<td>
							<select size="1" name="resID" id="bldg" required="required">
								<option value=""<?php if (!$currentResidence) echo ' selected="selected"'; ?>></option>
								
								<?php foreach ($residences as $residence): ?>
								<option value="<?php echo $residence->ID(); ?>"<?php if ($currentResidence && $currentResidence->ID() == $residence->ID()) echo ' selected="selected"'; ?>><?php echo $residence->Name; if (!$residence->NameUnique()) echo ' ('.$residence->Address.')'; ?></option>
								<?php endforeach; ?>
								
								<option value="-"<?php if ($isCustom) echo ' selected="selected"'; ?>>(Other)</option>
							</select>
							
							<!-- NOTE: These next two spans are wired up with jQuery -->
							<span id="usualapt"<?php echo $isCustom ? ' style="display: none;"' : '' ?>>
								&nbsp; Unit:
								<input type="text" size="4" maxlength="4" name="aptnum" required="required"<?php echo $unit ? 'value="'.htmlentities($unit).'"' : ''; ?>>
								<!--<small>(apartment number, if applicable)</small>-->
							</span>
							<span id="otherapt"<?php echo $isCustom ? '' : ' style="display: none;"' ?>>
								<br>Your <i>full</i> address:<span class="req">*</span><br>
								<input type="text" size="45" maxlength="255" id="address" name="address"<?php echo $isCustom ? 'value="'.htmlentities($currentResidence->String()).'"' : ''; ?>>
							</span>
							
							<input type="hidden" name="streetAddress" id="streetAddress" value="">
							<input type="hidden" name="city" id="city" value="">
							<input type="hidden" name="state" id="state" value="">
							<input type="hidden" name="zipcode" id="zipcode" value="">
						</td>
					</tr>
					<tr style="background: #EEE;">
						<td></td>
						<td>
							Phone number
						</td>
						<td>
							<input type="tel" size="20" maxlength="45" name="phone" value="<?php echo htmlentities($MEMBER->PhoneNumber); ?>">
							<br><input type="checkbox"<?php if ($MEMBER->HidePhone) echo ' checked="checked"'; ?> value="1" name="hidePhone" id="hidePhone"><label for="hidePhone"> Private</label>
							<br><input type="checkbox"<?php if ($MEMBER->ReceiveTexts) echo ' checked="checked"'; ?> value="1" name="receiveSms" id="receiveSms"><label for="receiveSms"> Receive occasional ward-related texts</label>
						</td>
					</tr>
					<tr>
						<td colspan="3">
							<hr>
							<h4>Profile picture</h4>
						</td>
					</tr>
					<tr>
						<td></td>
						<td id="pic">Current picture</td>
						<td>
							<img src="<?php echo $profilePic; ?>" id="ppic">
						</td>
					</tr>
					<tr style="background: #EEE;">
						<td></td>
						<td>
							Choose new picture
						</td>
						<td>
							<input type="file" size="25" name="profilepic" id="profilepic" accept="image/jpeg"> (JPG only; 2 MB max.)
							<br><i><small>Even if the ward takes pictures of everyone this<br>
							semester, you can still use your own if you want.<br>Please
							choose a picture with just you in it.</small></i>
						</td>
					</tr>
					<tr>
						<td colspan="3">
							<hr>
							<h4>For login</h4>
						</td>
					</tr>
					<tr>
						<td class="reqtd"><span class="req">*</span></td>
						<td style="min-width: 200px;">
							Email address
						</td>
						<td>
							<input type="email" size="35" maxlength="255" name="email" required="required" value="<?php echo htmlentities($MEMBER->Email); ?>">
							<br><input type="checkbox"<?php if ($MEMBER->HideEmail) echo ' checked="checked"'; ?> value="1" name="hideEmail" id="hideEmail"><label for="hideEmail"> Private</label>
						</td>
					</tr>
					<tr>
						<td colspan="3">
							<hr>
							<h4>Change your password</h4>
							<p>(Leave these blank to keep the same password.)</p>
						</td>
					</tr>
					<tr style="background: #EEE;">
						<td></td>
						<td>
							Current password
						</td>
						<td>
							<input type="password" size="35" maxlength="255" name="oldpwd">
						</td>
					</tr>
					<tr>
						<td></td>
						<td>
							New password
						</td>
						<td>
							<input type="password" size="35" maxlength="255" name="pwd1">
							<small><br>(At least 8 characters long; use letters, numbers, and symbols.)</small>
						</td>
					</tr>
					<tr style="background: #EEE;">
						<td></td>
						<td>
							Type password again
						</td>
						<td>
							<input type="password" size="35" maxlength="255" name="pwd2">
						</td>
					</tr>
				</table>



				<br><br>

				<p class="text-center">
					<button type="submit" class="button" id="subm">
						<span>&#10003;</span>
						Save profile
					</button>
					<img src="images/ajax-loader.gif" style="visibility: hidden; position: relative; top: 10px; left: 10px;" id="ajaxloader">
				</p>
			</form>
			<hr>
			<p class="text-center"><b>Switch to:</b> <a href="answers.php">Edit survey answers</a></p>
		</section>
		
	</article>


<script type="text/javascript" src="//d79i1fxsrar4t.cloudfront.net/jquery.liveaddress/2.4/jquery.liveaddress.min.js"></script>
<script type="text/javascript">

var liveaddress = $.LiveAddress({
	key: "<?php echo SMARTYSTREETS_HTML_KEY; ?>",
	autoMap: false
});

// NOTE: This is a little inconvenient. It'd be nice if we could get the chosen address
// (similar to chosenCandidate) no matter what path they took, whether AddressWasValid
// or UsedSuggestedAddress, or even if they opted to use their own instead, all in one place.
// (This is a memo for SmartyStreets....)
liveaddress.on("AddressWasValid", function(event, data, previousHandler)
{
	fillOutAddressFields(data.response.raw[0]);
	previousHandler(event, data);
});

liveaddress.on("UsedSuggestedAddress", function(event, data, previousHandler)
{
	fillOutAddressFields(data.chosenCandidate);
	previousHandler(event, data);
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
		previousHandler(event, data);
	}
});

<?php if ($isCustom): ?>
	// This feels terribly hacky...
	$('#usualapt input').removeAttr('required');
	liveaddress.mapFields("auto");
<?php endif; ?>

function fillOutAddressFields(responseAddr)
{
	$('#streetAddress').val(responseAddr.delivery_line_1);
	$('#city').val(responseAddr.components.city_name);
	$('#state').val(responseAddr.components.state_abbreviation);
	$('#zipcode').val(responseAddr.components.zipcode); 	// 5-digit only; this is on purpose
}

$(function() {

	var addrCache = $('#address').val(); 	// Stores a custom address in case the user hides then displays it again (cross-browserness)


	// From: http://malsup.com/jquery/form/progress.html
	// (Also on GitHub; documentation is lame: https://github.com/malsup/form)
	// The code below supports a progress bar which I haven't bothered
	// to implement yet. (*love* this...)

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

			$('#subm span').html('<img src="images/loader3.gif">');
			$('#subm').prop('disabled', true);
		},
		uploadProgress: function(event, position, total, percentComplete)
		{
			//var percentVal = percentComplete + '%';
			//bar.width(percentVal)
			//percent.html(percentVal);
		},
		complete: function(xhr)
		{
			if (xhr.status == 200)
			{
				_gaq.push(['_trackEvent', 'Account', 'Submit Form', 'Edit Profile']);
				toastr.success("Saved your profile!");
				if (hasPic)
				{
					$.hijax({
						url: 'api/currentmemberpicturepath.php',
						complete: function(xhr) {
							$('#ppic').show().attr('src', xhr.responseText);
							hasPic = false;
						}
					});
				}
			}
			else
				toastr.error(xhr.responseText);

			$('#subm span').html('&#10003;');
			$('#subm').prop('disabled', false);
		}
	});


	$("#bldg").change(function()
	{
		if ($(this).val() == "-")
		{
			// Show the "full address" field
			$('#usualapt input').removeAttr('required');
			$('#address').val(addrCache);
			$('#usualapt').hide();
			$('#otherapt').show();
			$('#otherapt input').attr('required', 'required').focus();
			liveaddress.mapFields("auto");
		}
		else
		{
			// Show the "unit number" field
			$('#otherapt input').removeAttr('required');
			$('#otherapt').hide();
			addrCache = $('#address').val();
			$('#address').val();
			$('#usualapt').show().attr('required', 'required');
			$('#usualapt input').focus();
			liveaddress.deactivate();
		}
	});
});
</script>

<?php include("includes/footer.php"); ?>