<?php
require_once "lib/init.php";

if ($MEMBER || $LEADER)	// If logged in... just go to directory.
	header("Location: /directory.php");

if (!isset($_GET['key']))
	die("Oops! Couldn't find any password reset token. Make sure you clicked on the link in the email or copied the entire link... ");

// Valid key?
$key = '';
$credID = 0;
if (isset($_GET['key']))
{
	$key = DB::Safe($_GET['key']);
	$q = "SELECT * FROM PwdResetTokens WHERE Token='{$key}' LIMIT 1";
	$r = DB::Run($q);

	if (mysql_num_rows($r) == 0)
		die("ERROR > Sorry, that is not a valid password reset token. Please go back to your email and try again?");

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
		DB::Run("DELETE FROM PwdResetTokens WHERE ID='$tokenID' LIMIT 1");
		die("ERROR > Sorry, that token has expired. They only last 48 hours.");
	}
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Finish password reset &mdash; <?php echo SITE_NAME; ?></title>
		<?php include "includes/head.php"; ?>
	</head>
	<body class="narrow">

		<form method="post" action="/api/resetpwd-finish.php">
			<div class="text-center">
				<a href="/">
					<img src="<?php echo SITE_LARGE_IMG; ?>" alt="<?php echo SITE_NAME; ?>" class="logo-big">
				</a>

				<h1>Reset Password</h1>

				<p>
					Type your new password below, then try logging in.
					Don't use the same password you use on other sites.
				</p>

				<input type="password" name="pwd1" placeholder="New password" required>
				<input type="password" name="pwd2" placeholder="Password again" required>

				<input type="hidden" name="credID" value="<?php echo($credID); ?>">
				<input type="hidden" name="token" value="<?php echo($key); ?>">

				<div class="text-right">
					<button type="submit">Finish</button>
				</div>
				<br>

			</div>
		</form>

		<?php include "includes/footer.php"; ?>



<script>
$(function()
{
	$("input").first().focus();	// TODO Make this the thing for every page?

	var redirecting = false;

	$('form').hijax({
		before: function()
		{
			$('[type=submit]').showSpinner();
			return !redirecting;
		},
		complete: function(xhr)
		{
			if (xhr.status == 200)
			{
				_gaq.push(['_trackEvent', 'Account', 'Submit Form', 'Reset Finished']);
				$.sticky("Password changed! Going to login...");
				setTimeout(function() { window.location = '/'; }, 3500);
				redirecting = true;
			}
			else
			{
				$.sticky(xhr.responseText || "Something went wrong. Check your connection and try again.", { classList: 'error' });
				$('[type=submit]').hideSpinner();
			}
		}
	});
});
</script>

	</body>
</html>