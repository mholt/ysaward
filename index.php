<?php
require_once("lib/init.php");

if (Member::IsLoggedIn() || StakeLeader::IsLoggedIn())
	header("Location: /directory.php");

//header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
//header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
?>
<!DOCTYPE html>
<html class="bluebg">
<head>
	<title>Welcome &mdash; <?php echo SITE_NAME; ?></title>
	<?php include("includes/head.php"); ?>
<style>
button[type=submit] {
	max-width: 150px;
	display: inline;
}

#login-form input, [type=submit] {
	display: block;
	width: 90%;
	max-width: 275px;
}
</style>
</head>
<body>
	
	<div class="grid-12">
		<section class="g-12 pad-top"></section>
		<hr class="clear">
		
		<section class="g-4 text-center">
			<br><br><br>
			<img src="<?php echo SITE_LARGE_IMG; ?>" alt="<?php echo SITE_NAME; ?>">
		</section>
		
		<section class="g-4">
			<h1>login</h1>
			<form method="post" action="login.php" id="login-form">
				<input type="email" name="eml" placeholder="Email address" id="eml" required="required">
				<input type="password" name="pwd" placeholder="Password" id="pwd" required="required">
				<button type="submit">
					Login <span>&raquo;</span>
				</button>
				&nbsp; &nbsp;
				<a href="resetpwd.php">Can't&nbsp;login?</a>
			</form>
		</section>
		
		<section class="g-4">
			
			<h1>sign up</h1>
			<p>
				Register so your ward can get your
				membership records. You'll also get a directory
				tailored specifically to you, FHE groups,
				and more.
			</p>

			<a href="register.php" id="reglink" class="button">Register</a>
			
		</section>
		<hr class="clear">
	
	</div>

<script type="text/javascript">
$(function() {

	$('#eml').focus();

	// Login form submitted
	$('#login-form').hijax({
		before: function() {
			$('button[type=submit] span').html('<img src="images/loader4.gif">');
		},
		complete: function(xhr) {
			if (xhr.status == 200)
				window.location = '/directory.php?login=1';
			else
			{
				toastr.error("Wrong email/password combination.", "Bad credentials");
				$('button[type=submit] span').html('&raquo;');
			 }
		}
	});
});
</script>

<?php include("includes/footer.php"); ?>