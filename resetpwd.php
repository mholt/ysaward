<?php
require_once("lib/init.php");

if (Member::IsLoggedIn())
	header("Location: /directory.php");
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Reset password &mdash; <?php echo SITE_NAME; ?></title>
		<?php include("includes/head.php"); ?>
	</head>
	<body class="narrow">

		<form method="post" action="/api/resetpwd.php">
			<div class="text-center">
				<a href="/">
					<img src="<?php echo SITE_LARGE_IMG; ?>" alt="<?php echo SITE_NAME; ?>" class="logo-big">
				</a>

				<h1>Reset Password</h1>

				<p>
					Type your email address
					below and you'll be sent a special link to reset
					your password.
				</p>

				<input type="email" name="eml" placeholder="Email address" required>

				<div class="text-right">
					<button type="submit">Continue</button>
				</div>

				<br>

			</div>
		</form>

		<?php include("includes/footer.php"); ?>


<script>
$(function()
{
	$('input').first().focus();
	
	// Reset pwd form submitted; sending email...
	$('form').hijax({
		before: function()
		{
			$('[type=submit]').showSpinner();
		},
		complete: function(xhr)
		{
			if (xhr.status == 200)
			{
				_gaq.push(['_trackEvent', 'Account', 'Submit Form', 'Request Reset']);
				$.sticky("An email with instructions has been sent to you. <br>Redirecting in a few seconds...");
				setTimeout(function() { window.location = '/' }, 5000);
			}
			else
			{
				$('[type=submit]').hideSpinner();
				$.sticky(xhr.responseText || "There was an error. Please check your connection and try again.", { classList: 'error' });
			}
		}
	});
});
</script>


	</body>
</html>