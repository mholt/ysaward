<?php
require_once("lib/init.php");
protectPage();

// TODO: Ensure the user is a clerk, secretary, or bishopric member
// Right now we're only checking to see if they have a "preset" calling
// which includes EQP and RSP. (Also see api/exportmembersmls.php for this)
if (!$MEMBER || !$MEMBER->HasPresetCalling())
{
	header("Location: /directory.php");
	exit;
}

$months = array("January", "February", "March", "April",
	"May", "June", "July", "August", "September", "October",
	"November",	"December");

?>
<html>
<head>
	<title>Export to MLS &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
	<?php include("includes/head.php"); ?>
<style>

</style>
</head>
<body>
	
	<?php include("includes/header.php"); ?>
	
	<article class="grid-12 group">
		
		<section class="g-12">

			<h1>Export to MLS</h1>
			
			<section class="g-12">
			<div class="instructions">
				<p>
					Use this page to request a batch of membership records in the church's MLS software.
				</p>
				<p>
					Simply choose a cutoff date and click the button. A file will be downloaded
					to your computer. Then use the MLS software to request multiple records.
					You will be asked for a file in a certain format containing the members'
					information. Supply it with the file generated from this page.
				</p>
				<p>
					Note that LDS.org membership tools do not yet have a feature to request
					multiple membership records at once.
				</p>
			</div><br>
		</section>
		<hr class="clear">
			
			

			<form method="post" action="api/exportmembersmls.php" class="text-center">
			
				<b>Include all members who signed up <i>on or after</i>:</b>
				
				<br><br>
				
				<select size="1" name="day" required="required">
					<option value="" selected="selected">(Day)</option>
				<?php
					for ($i = 1; $i <= 31; $i ++)
						echo "\t\t\t\t<option value=\"$i\">$i</option>\r\n";
				?>
				</select>
				<select size="1" name="month" required="required">
					<option value="" selected="selected">(Month)</option>
					<?php
						for ($i = 1; $i <= 12; $i ++)
							echo "\t\t\t\t<option value=\"$i\">".($months[$i - 1])."</option>\r\n";
					?>
				</select>
				<select size="1" name="year" required="required">
					<option value="" selected="selected">(Year)</option>
					<?php
						for ($i = date("Y"); $i >= 2011; $i --)
							echo "\t\t\t\t<option value=\"$i\">$i</option>\r\n";
					?>
				</select>
				
				<br><br>
				
				<input type="submit" class="button" value="Download for MLS">
				
			</form>
			
			<br><br>
			
			<p class="text-center" style="font-size: 12px;">
				The file you will download is in CSV format and contains
				members' names, addresses, and birth dates.<br>It would be wise
				to destroy the file when you are finished importing into MLS.
			</p>

		</section>
		
	</article>

<?php include("includes/footer.php"); ?>