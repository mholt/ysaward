<?php
require_once("lib/init.php");

// Springboard if logged in
if (Member::IsLoggedIn())
	header("Location: /directory.php");


// If user hasn't entered a ward password yet, prompt for it.
// This keeps the creepers out.
if (!isset($_SESSION['wardie']) || !isset($_SESSION['ward_id']))
{
	// Build the list of wards by stake
	$r = DB::Run("SELECT `ID`, `Name`, `StakeID` FROM `Wards` WHERE `Deleted` != 1 ORDER BY `StakeID`, `Name`");

	$stakes = array();

	while ($row = mysql_fetch_array($r))
	{
		$sid = $row['StakeID'];
		$wid = $row['ID'];

		if (!array_key_exists($sid, $stakes))
			$stakes[$sid] = array();

		$stakes[$sid][] = $wid;
	}
?>
<!DOCTYPE html>
<html class="bluebg">
<head>
	<title>Register &mdash; <?php echo SITE_NAME; ?></title>
	<?php include("includes/head.php"); ?>
	<style>
.lbl {
	white-space: nowrap;
	width: 200px;
	vertical-align: top;
	padding-top: 2em;
}

td {
	padding: 10px 0px 5px;
}

.nopad {
	padding-top: 1.1em;
}
	</style>
</head>
<body>
	<div class="grid-12">
		<br>
		<section class="g-4 text-center">
			<br><br>
			<a href="/"><img src="<?php echo SITE_LARGE_IMG; ?>" alt="<?php echo SITE_NAME; ?>"></a>
		</section>
		
		<section class="g-8">		
			
			
			<h1>registration</h1>

			<p>
				Please choose your ward and type the ward password to continue.
			</p>

			<form id="preregister" method="post" action="/api/wardpwd.php" style="line-height: 1em;">
				<table>
					<tr>
						<td><span class="req">*</span> Select your ward:</td>
						<td class="nopad">
							<select size="1" name="ward_id" required="required">
								<option value="" selected="selected"></option>
							<?php
							foreach ($stakes as $sid => $wards)
							{
								$stakeObj = Stake::Load($sid);
							?>
								<optgroup label="<?php echo $stakeObj->Name; ?>">
							<?php
								foreach ($wards as $wid)
								{
									// Get the bishop's name, if any.
									$ward = Ward::Load($wid);
									$bishop = $ward->GetBishop();
							?>
									<option value="<?php echo $wid; ?>"><?php echo $ward->Name; if ($bishop) echo " (Bishop ".$bishop->LastName.")"; ?></option>
							<?php
								}
							?>
								</optgroup>
							<?php
							}
							?>
							</select>
						</td>
					</tr>
					<tr>
						<td class="lbl"><span class="req">*</span> Ward password:</td>
						<td>
							<input type="password" name="pwd" size="25" required="required">
						</td>
					</tr>
					<tr>
						<td></td>
						<td>
							<input type="submit" value="Continue &raquo;">
						</td>
					</tr>
					
				</table>
			</form>
			
		</section>
		<hr class="clear">	
	</div>	
	
<script type="text/javascript">
$(function() {
	$('select').focus();

	$('select').change(function() {
		$('input[type=password]').focus();
	});
	
	// Form was submitted
	$('#preregister').ajaxForm({
		complete: function(xhr) {
			if (xhr.status == 200)
			{
				// Correct ward/password combination; proceed to registration form.
				window.location = '/register.php';
			}
			else
				toastr.error(xhr.responseText || "Could not authenticate you, sorry. Please try again, or try later.");
		}
	});
});
</script>
<?php include("includes/footer.php"); ?>


<?php
}
else
{
	// User has chosen his/her ward and typed the correct password.

	$wid = $_SESSION['ward_id'];
	$ward = Ward::Load($wid);

	if ($ward->Deleted)
		fail("Sorry, for whatever reason, that ward is no longer available on this site.");

	// Get a list of residences in the ward so the user can conveniently pick one
	$residences = $ward->Residences();
?>
<!DOCTYPE html>
<html class="bluebg">
<head>
	<title>Register &mdash; <?php echo SITE_NAME; ?></title>
	<?php include("includes/head.php"); ?>
<style>
.lbl {
	white-space: nowrap;
	width: 200px;
	vertical-align: top;
	padding-top: 2em;
}

td {
	padding: 10px 0px 5px;
}

input[type=file] {
	cursor: pointer;
}

.nopad {
	padding-top: 1.1em;
}
</style>
</head>
<body>
	<div class="grid-12">
		<br>
		<section class="g-4 text-center">
			<br>
			<a href="/"><img src="<?php echo SITE_LARGE_IMG; ?>" alt="<?php echo SITE_NAME; ?>"></a>
		</section>
		
		<section class="g-8">		
			
			
			<h1>registration (page 1 / 2)</h1>

			<p style="background: #F9E572; text-shadow: none; padding: 5px;">
				<b>Registering with:</b> <?php echo $ward->Name; ?> Ward
			</p>

			<p>
				You'll be interested to know that:
				<ul>
					<li>Everyone in the ward is encouraged to make an account here</li>
					<li>This site serves as a ward directory, available anywhere</li>
					<li>The ward directory is private and helps others to know who you are</li>
					<li>Details about you stay private and are <i>only</i> available to those who <i>need</i> to know</li>
				</ul>
			</p>

			<p>
				<b>Please fill out valid and complete information so that your records may be requested, if necessary.
				<br>
				Fields marked with <span class="req">*</span> are required.</b> Thanks!
			</p>


			<form id="register-form" method="post" action="/api/register.php" enctype="multipart/form-data" style="line-height: 1em;">
				<table>
					<tr>
						<td class="lbl"><span class="req">*</span> Email address:</td>
						<td><input type="email" name="email" required="required" id="eml" size="30">
						<br>
						<label><input type="checkbox" name="hideEmail"> Private (hide in ward directory)</label></td>
					</tr>
					<tr>
						<td class="lbl"><span class="req">*</span> Password:</td>
						<td>
							<input type="password" name="pwd1" required="required">
							<br><small><i>Must be at least 8 characters long.</i></small><br><br>
						</td>
					</tr>
					<tr>
						<td class="lbl"><span class="req">*</span> Password again:</td>
						<td><input type="password" name="pwd2" required="required"><br></td>
					</tr>
					<tr>
						<td class="lbl"><span class="req">*</span> First name:</td>
						<td><input type="text" name="fname" required="required"></td>
					</tr>
					<tr>
						<td class="lbl">Middle name or initial:</td>
						<td>
							<input type="text" name="mname">
							<br><small><i>Please provide your full name so we can get your records in.</i></small><br><br>
						</td>
					</tr>
					<tr>
						<td class="lbl"><span class="req">*</span> Last name:</td>
						<td><input type="text" name="lname" required="required"></td>
					</tr>
					<tr>
						<td class="lbl nopad"><span class="req">*</span> Date of birth:</td>
						<td>
							<select size="1" name="day" required="required">
								<option value="" selected="selected">(Day)</option>
								<?php
									for ($i = 1; $i < 32; $i ++)
										echo "	<option value=\"$i\">$i</option>\r\n";
								?>
							</select>
	
							<select size="1" name="month" required="required">
								<option value="" selected="selected">(Month)</option>
								<option value="01">January</option>
								<option value="02">February</option>
								<option value="03">March</option>
								<option value="04">April</option>
								<option value="05">May</option>
								<option value="06">June</option>
								<option value="07">July</option>
								<option value="08">August</option>
								<option value="09">September</option>
								<option value="10">October</option>
								<option value="11">November</option>
								<option value="12">December</option>
							</select>
	
							<select size="1" name="year" required="required">
								<option value="" selected="selected">(Year)</option>
								<?php
									for ($i = date("Y") - 16; $i >= date("Y") - 75; $i --)
										echo "	<option value=\"$i\">$i</option>\r\n";
								?>
							</select> <i><small>(Year won't be shown.)</small></i>
							<br>
							<label><input type="checkbox" value="1" name="hideBirthday" id="hideBirthday"> Private (hide entire date)</label>
						</td>
					</tr>
					<tr>
						<td class="lbl nopad"><span class="req">*</span> Gender:</td>
						<td>
							<input type="radio" name="gender" value="<?php echo Gender::Male; ?>" id="male" required="required"><label for="male"> Male</label><br>
							<input type="radio" name="gender" value="<?php echo Gender::Female; ?>" id="female" required="required"><label for="female"> Female</label>
							<br><br>
						</td>
					</tr>
					<tr>
						<td class="lbl nopad">
							<br><span class="req">*</span> Apartment:
						</td>
						<td>
							<select size="1" name="resID" id="bldg" required="required">
								<option value="" selected="selected"></option>
								
								<?php foreach ($residences as $residence): ?>
								<option value="<?php echo $residence->ID(); ?>"><?php echo $residence->Name; if (!$residence->NameUnique()) echo ' ('.$residence->Address.')'; ?></option>
								<?php endforeach; ?>
								
								<option value="-">(Other)</option>
							</select>
							
							<!-- NOTE: These next two spans are wired up with jQuery -->
							<span id="usualapt">
								&nbsp; Unit:
								<input type="text" size="4" maxlength="4" name="aptnum" required="required">
								<!--<small>(apartment number, if applicable)</small>-->
							</span>
							<span id="otherapt" style="display: none;">
								<br>Your <i>full</i> address:<span class="req">*</span><br>
								<input type="text" size="45" maxlength="255" id="address" name="address">
							</span>
							
							<input type="hidden" name="streetAddress" id="streetAddress" value="">
							<input type="hidden" name="city" id="city" value="">
							<input type="hidden" name="state" id="state" value="">
							<input type="hidden" name="zipcode" id="zipcode" value="">
						</td>
					</tr>
					<tr>
						<td class="lbl">
							Phone number:
						</td>
						<td>
							<input type="tel" size="20" maxlength="45" name="phone">
							<br><input type="checkbox" value="1" name="hidePhone" id="hidePhone"><label for="hidePhone"> Private (hide in ward directory)</label>
						</td>
					</tr>
					<tr>
						<td class="lbl">
							Profile picture:
						</td>
						<td>
							<input type="file" size="25" name="profilepic" accept="image/jpeg">
							<br>(2 MB max; JPG only)
							<br><br><i><small>Even if the ward takes pictures of everyone this<br>
							semester, you can still use your own if you want.<br>Please
							choose a picture with just you in it.</small></i>
	
							<br><br>
						</td>
					</tr>
					<tr>
						<td></td>
						<td>
							<input type="submit" value="Next page &raquo;">
						</td>
					</tr>
					
				</table>
			</form>
			
		</section>
		<hr class="clear">	
	</div>	
	
<script type="text/javascript" src="//d79i1fxsrar4t.cloudfront.net/jquery.liveaddress/2.4/jquery.liveaddress.min.js"></script>
<script type="text/javascript">

var liveaddress = $.LiveAddress({
	key: "<?php echo SMARTYSTREETS_HTML_KEY; ?>",
	autoMap: false
});


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

function fillOutAddressFields(responseAddr)
{
	$('#streetAddress').val(responseAddr.delivery_line_1);
	$('#city').val(responseAddr.components.city_name);
	$('#state').val(responseAddr.components.state_abbreviation);
	$('#zipcode').val(responseAddr.components.zipcode);	// 5-digit only; this is on purpose
}
$(function() {

	var addrCache = $('#address').val(); 	// Stores a custom address in case the user hides then displays it again (cross-browserness)
	
	$('#bldg').change(function()
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

	$('#eml').focus();
	
	// Registration form was submitted
	$('#register-form').ajaxForm({
		complete: function(xhr) {
			if (xhr.status == 200)
			{
				// They've been logged in, so take them to the answers page
				window.location = '/answers.php?new';
			}
			else
				toastr.error(xhr.responseText || "There was a problem... sorry. Please contact your ward website person to get this resolved.");
		}
	});
});
</script>
<?php include("includes/footer.php"); ?>

<?php } ?>