<?php
require_once("lib/init.php");

if (Member::IsLoggedIn() || StakeLeader::IsLoggedIn())
	header("Location: /directory");
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Welcome &mdash; <?php echo SITE_NAME; ?></title>
		<?php include("includes/head.php"); ?>
		<!-- Facebook OpenGraph tags (for sharing) -->
		<meta name="description" content="Sign up so your ward can get your membership records. You'll also get a custom directory and abilities to text and email.">
		<meta property="og:image" content="http://<?php echo $_SERVER['SERVER_NAME']; ?><?php echo SITE_LARGE_IMG; ?>">
		<meta property="og:title" content="Welcome &mdash; <?php echo SITE_NAME; ?>">
		<meta property="og:site_name" content="<?php echo SITE_NAME; ?>">
	</head>
	<body class="narrow">
		<div id="content">

			<form method="post" action="api/login.php">

				<div class="text-center">
					<img src="<?php echo SITE_LARGE_IMG; ?>" alt="<?php echo SITE_NAME; ?>" class="logo-big">
					<br>

					<big>Please <a href="register.php">register</a> if you need an account.</big>

					<hr>

					<input type="email" name="eml" placeholder="Email address" required>
					<input type="password" name="pwd" placeholder="Password" required>
				</div>

				<div class="text-right">
					<button type="submit">Login</button><br>
					<a href="resetpwd.php">Reset password</a><br>
					<br>
				</div>

			</form>

			<?php include("includes/footer.php"); ?>

		</div>

<script>
$(function()
{
	$('input').first().focus();

	$('form').hijax({
		before: function()
		{
			$('button[type=submit]').showSpinner();
		},
		complete: function(xhr)
		{
			if (xhr.status == 200)
			{
				if (!xhr.responseText || xhr.responseText == "")
					window.location = "/directory?login=1";
				else
					window.location = xhr.responseText.indexOf('login=1') > -1
						? xhr.responseText
						: xhr.responseText + "?login=1";
			}
			else
			{
				$.sticky("Wrong email/password combination.", { classList: 'error' });
				$('button[type=submit]').hideSpinner();
			 }
		}
	});
});
</script>
	</body>
</html>