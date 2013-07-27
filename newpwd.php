<?php
require_once("lib/init.php");

if ($MEMBER || $LEADER)	// If logged in... just go to directory.
	header("Location: /directory.php");

if (!isset($_GET['key']))
	die("ERROR > Make sure you clicked on the link in the email or copied the full link... could't find the reset token.");

// Valid key?
$key = '';
$credID = 0;
if (isset($_GET['key']))
{
	$key = DB::Safe($_GET['key']);
	$q = "SELECT * FROM PwdResetTokens WHERE Token='{$key}' LIMIT 1";
	$r = DB::Run($q);

	if (mysql_num_rows($r) == 0)
		die("ERROR > Sorry, that is not a valid password reset token. Please try again...");

	// Get the associated credentials ID...
	$row = mysql_fetch_array($r);
	$credID = $row['CredentialsID'];
	if (!$credID)
		die("ERROR > That token doesn't seem associated with any account...");

	// Make sure it hasn't expired; delete it if it has
	$tokenID = $row['ID'];
	$tooLate = strtotime("+48 hours", strtotime($row['Timestamp']));
	if (time() > $tooLate)
	{
		$q = "DELETE FROM PwdResetTokens WHERE ID='$tokenID' LIMIT 1";
		DB::Run($q);
		die("ERROR > Sorry, that token has expired. They only last 48 hours.");
	}
}
?>
<!DOCTYPE html>
<html class="bluebg">
<head>
	<title>Finish Resetting Password &mdash; <?php echo SITE_NAME; ?></title>
	<?php include("includes/head.php"); ?>
</head>
<body>
	<div class="grid-12">
		<br>
		
		<section class="g-4 text-center">
			<br><br>
			<a href="/"><img src="/images/ysa2-lg.png" alt="<?php echo SITE_NAME; ?>"></a>
		</section>
		
		<section class="g-6 suffix-2">		
			
			<h1>finish password reset</h1>
			
			
			<form method="POST" action="/api/resetpwd-finish.php">
				<p>Type your new password below, then try logging in.</p>
				
				<p>Your new password should:</p>
				
				<ul>
					<li>be at least 8 characters long</li>
					<li>use letters, numbers, and symbols</li>
					<li>be unique from other passwords you use</li>
				</ul>
				
	
				<input type="hidden" name="credID" value="<?php echo($credID); ?>">
				<input type="hidden" name="token" value="<?php echo($key); ?>">
				<b>New password:</b> <input type="password" name="pwd1">
				<br>
				<b>Password again:</b> <input type="password" name="pwd2"><br><br>
				<input type="submit" value="&nbsp; &#10003; Finish &nbsp;">
			</form>
			
		</section>
		<hr class="clear">	
	</div>

<script type="text/javascript">
$(function() {
	$("input[name=pwd1]").focus();

	var redirecting = false;

	$('form').hijax({
		before: function() {
			return !redirecting;
		},
		complete: function(xhr) {
			if (xhr.status == 200)
			{
				_gaq.push(['_trackEvent', 'Account', 'Submit Form', 'Reset Finished']);
				toastr.success("Password changed! Redirecting to login...");
				setTimeout(function() { window.location = '/'; }, 3500);
				redirecting = true;
			}
			else
				toastr.error(xhr.responseText);
		}
	});
});
</script>

<?php include("includes/footer.php"); ?>
