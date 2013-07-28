<?php
/*
	This file helps set up a new ward on the system.
	It should be secured so that only the webmaster can access it.
*/

exit;	// SAFETY. Comment-out or remove this line to use this file.




require_once("../lib/init.php");

if (!isset($_POST['stakeID']) || !isset($_POST['wardName']) || !isset($_POST['gender'])):

	$qStakes = DB::Run("SELECT * FROM Stakes ORDER BY Name ASC");

?>
<!DOCTYPE html>
<html>
<head>
	<title>Initialize Ward</title>
<style>
body { padding: 15px 35px; }
</style>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	<script type="text/javascript" src="//d79i1fxsrar4t.cloudfront.net/jquery.liveaddress/2.4/jquery.liveaddress.min.js"></script>
	<script type="text/javascript">
		var liveaddress = jQuery.LiveAddress({ key: "<?php echo SMARTYSTREETS_HTML_KEY; ?>", submitVerify: false });

		// Keep only the 5-digit ZIP code
		liveaddress.on("AddressAccepted", function(event, data, previousHandler)
		{
			if (data.response && data.response.raw.length > 0)
			{
				var zipField = data.address.getDomFields()['zipcode'];
				zipField.value = data.response.raw[0].components.zipcode;
			}
			previousHandler(event, data);
		});
	</script>
</head>
<body>
	<h1>Initialize New Ward</h1>
	
	<b>This should be done with a developer and an executive secretary, ward clerk, or member of the bishopric present.</b>
	
	<hr>
	<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
	
		<h2>Ward Info</h2>

		Stake:
		<select size="1" name="stakeID" required>
			<option value="" selected>(Select one)</option>
			<?php while ($r = mysql_fetch_array($qStakes)):  ?>
			<option value="<?php echo $r['ID']; ?>"><?php echo $r['Name']; ?></option>
			<?php endwhile; ?>
		</select>
		
		<br><br>
		
		Ward Name:
		<input type="text" size="30" name="wardName" required> <i>(example: "Provo YSA 20th")</i>

		<br><br>
		
		Ward Password:
		<input type="text" size="25" name="wardPwd" required> <i>(Something the whole ward can remember, but hard to guess)</i>
		
		<br><br>
		
		Residences in the ward:<br><small><i>(Prefixes the apartment numbers)</i></small>
		<br><hr>
		<table style="margin-left: 10px;">
			<tr>
				<th style="width: 250px;">Complex Name<br><small><i>(Like "Regency" or "Park Plaza"; <b>required</b>)</i></small></th>
				<th style="width: 300px;">Street Address</th>
				<th style="width: 150px;">City</th>
				<th style="width: 100px;">State</th>
				<th style="width: 100px;">ZIP Code</th>
			</tr>	
		</table>
		<hr>
		<ol>
			<?php for ($i = 0; $i < 10; $i ++): ?>
			<li style="margin-bottom: 10px;">
				<table style="margin-top: -1.5em;">
					<tr>
						<td style="width: 250px;"><input type="text" name="resnames[]" size="30"></td>
						<td style="width: 300px;"><input type="text" name="streets[]" size="40"></td>
						<td style="width: 150px;"><input type="text" name="cities[]" size="20"></td>
						<td style="width: 100px;"><input type="text" name="states[]" size="3"></td>
						<td style="width: 100px;"><input type="text" name="zips[]" size="5"></td>
					</tr>
				</table>
			</li>
			<?php endfor; ?>
		</ol>
		
		<br><br>
				
		<h2>First Member Account (you)</h2>
		
		<i>(These can all be changed later)</i><br><br>
		
		
		First name:
		<input type="text" size="25" name="firstName" required>
		
		<br><br>
		
		Last name:
		<input type="text" size="25" name="lastName" required>
		
		<br><br>
		
		Gender: &nbsp; &nbsp;
		<label><input type="radio" name="gender" value="<?php echo Gender::Male; ?>" required checked> Male</label> &nbsp;
		<label><input type="radio" name="gender" value="<?php echo Gender::Female; ?>" required> Female</label>
		
		<br><br>
		
		Birthday:
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
		&nbsp; 
		<label><input type="checkbox" value="1" name="hideBirthday" id="hideBirthday"> Private (hide entire date)</label>
		
		<br><br>
		
		
		Email address:
		<input type="email" size="40" name="email" required> <i>(Used for login)</i>
		
		<br><br>
		
		Password:
		<input type="password" size="25" name="pwd1" required>
		
		<br><br>
		
		Re-type Password:
		<input type="password" size="25" name="pwd2" required>
		
		<br><br>
		
		Calling:
		<select size="1" name="callingName" required>
			<option value="" selected>(Select one)</option>
			<option value="Bishop">Bishop</option>
			<option value="Bishopric 1st Counselor">Bishopric 1st Counselor</option>
			<option value="Bishopric 2nd Counselor">Bishopric 2nd Counselor</option>
			<option value="Executive Secretary">Executive Secretary</option>
			<option value="Ward Clerk">Ward Clerk</option>
			<option value="Membership Clerk">Membership Clerk</option>
		</select>
		
		
		<br><br><br><br><br><br>
		
		<b>... PLEASE MAKE SURE EVERYTHING IS CORRECT AND COMPLETE ...</b>

		<br><br><br><br><br><br>
		
		<input type="submit" value="CREATE WARD" style="font-size: 16px;">
		
		<br><br><br><br><br><br>
		
	</form>
</body>
</html>
<?php
else:

	@ $stakeID = $_POST['stakeID'];
	@ $wardName = trim($_POST['wardName']);
	@ $wardPwd = trim($_POST['wardPwd']);
	@ $resnames = $_POST['resnames'];
	@ $streets = $_POST['streets'];
	@ $cities = $_POST['cities'];
	@ $states = $_POST['states'];
	@ $zips = $_POST['zips'];
	@ $firstName = trim($_POST['firstName']);
	@ $lastName = trim($_POST['lastName']);
	@ $gender = $_POST['gender'];
	@ $email = trim($_POST['email']);
	@ $pwd1 = $_POST['pwd1'];
	@ $pwd2 = $_POST['pwd2'];
	@ $callingName = $_POST['callingName'];
	@ $day = $_POST['day'];
	@ $month = $_POST['month'];
	@ $year = $_POST['year'];
	@ $hideBirthday = $_POST['hideBirthday'];

	if (!$stakeID || !$wardName || !$wardPwd || !$firstName || !$lastName || !$gender || !$email || !$pwd1 || !$pwd2 || !$callingName || !$day || !$month || !$year)
		fail("All fields are required... please go back and double-check your input.");

	if ($pwd1 != $pwd2)
		fail("The account passwords you typed don't match. Make sure they match exactly.");

	if ($gender != Gender::Male && $gender != Gender::Female)
		fail("Bad gender value.");

	$safe_email = mysql_real_escape_string($email);
	$q1 = DB::Run("SELECT 1 FROM Credentials WHERE Email='{$safe_email}'");
	if (mysql_num_rows($q1) > 0)
		fail("There's already a user with that email address. Please choose another or reconcile the accounts.");

	$ward = Ward::Create($wardName, $stakeID, $wardPwd);
	
	if ($ward != null)
		echo "Created {$ward->Name} Ward with ID {$ward->ID()}...<br>";
	else
		fail("Could not create ward... not sure why. Sorry. (\$ward is null)");

	// Save the user
	$user = new Member();
	$user->FirstName = $firstName;
	$user->LastName = $lastName;
	$user->Email = $email;
	$user->Gender = $gender;
	$user->SetPassword($pwd1);
	$user->WardID = $ward->ID();
	$user->Birthday = "{$year}-{$month}-{$day}";
	$user->HideBirthday = $hideBirthday;

	if ($user->Save())
		echo "Created user account...<br>";
	else
		fail("Created ward, but could not create user account! Oh noez!");

	
	// Assign that user a calling; first obtain the calling ID requested
	$safe_callingName = DB::Safe($callingName);
	$r = mysql_fetch_array(DB::Run("SELECT ID FROM Callings WHERE Name='{$safe_callingName}' AND WardID='{$user->WardID}' LIMIT 1"));
	$callingID = $r['ID'];

	if (!$callingID)
		fail("No calling ID for {$callingName} was found with ward ID {$user->WardID}...");

	if ($user->AddCalling($callingID))
		echo "Gave {$firstName} calling '{$callingName}' with ID {$callingID}.<br>";
	else
		fail("Could not assign calling {$callingName} with ID {$callingID} to {$firstName} {$lastName}. Sorry. (Calling may not exist in the ward.)");

	// Create Residences
	for ($i = 0; $i < count($resnames); $i ++)
		if (trim($resnames[$i]) != "")
			$ward->AddResidence($resnames[$i], $streets[$i], $cities[$i], $states[$i], $zips[$i]);

	echo "Completed successfully.<br>";
	echo "All done!<br><br><a href='/'>Home</a>";

endif;
?>