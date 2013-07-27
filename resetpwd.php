<?php
require_once("lib/init.php");

if (Member::IsLoggedIn())
	header("Location: /directory.php");
?>
<!DOCTYPE html>
<html class="bluebg">
<head>
	<title>Request Password Reset &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
	<?php include("includes/head.php"); ?>
</head>
<body>
	<div class="grid-12">
		<br><br><br>
		
		<section class="g-4 text-center">
			<br><br>
			<a href="/"><img src="<?php echo SITE_LARGE_IMG; ?>" alt="<?php echo SITE_NAME; ?>"></a>
		</section>
		
		<section class="g-6 suffix-2">		
			
			<h1>reset password</h1>

			<form method="post" action="/api/resetpwd.php">
				<p>
					Type your email address
					below and you'll be sent a special link to reset
					your password.
				</p>

				<b>Email address:</b> <input type="email" name="eml" id="eml" size="30" required="required">
				
				<br><br>
				<input type="submit" value="Continue &raquo;">
			</form>
			
		</section>
		<hr class="clear">	
	</div>
	
<script type="text/javascript">
$(function() {
	$('#eml').focus();
	
	// Reset pwd form submitted; sending email...
	$('form').hijax({
		before: function() {
			$('input[type=submit]').prop('disabled', true);
		},
		complete: function(xhr) {
			if (xhr.status == 200)
			{
				_gaq.push(['_trackEvent', 'Account', 'Submit Form', 'Request Reset']);
				toastr.success("An email with instructions has been sent to you. <br>Redirecting in a few seconds...");
				setTimeout(function() { window.location = '/' }, 7000);
			}
			else
			{
				$('input[type=submit]').prop('disabled', false);
				toastr.error(xhr.responseText);
			}
		}
	});
});
</script>


<?php include("includes/footer.php"); ?>