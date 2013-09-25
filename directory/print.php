<?php
require_once("../lib/init.php");
protectPage(0, true);

// Get the ward ID. "$WARD" is defined in init.php for convenience.
$wardID = DB::Safe($WARD->ID());

// Load a list of the members in order of apartment or address
$members = array();

$q = DB::Run("SELECT
	Members.ID,
	TRIM(CONCAT_WS(\" \", Residences.Address, Residences.City, Residences.State)) AS FullAddr,
	TRIM(CONCAT_WS(\" \", Residences.Name, Members.Apartment)) AS RegularAddr
FROM Members
LEFT JOIN Residences
ON Members.ResidenceID = Residences.ID
WHERE Members.WardID='{$WARD->ID()}'
ORDER BY RegularAddr, FullAddr, FirstName, LastName ASC;");

$lastApt = "";	// The apartment/address string of the last member in the loop
$i = 0;			// New line (float clearing) counter
$j = 0;			// Incremented for each apartment group we encounter
?>
<html>
<head>
	<title><?php echo $WARD->Name; ?> Ward Directory &mdash; <?php echo SITE_NAME; ?></title>
	<?php include "../includes/head.php"; ?>
	<style>

	html,
	body {
		background: #FFF;
		max-width: 7.5in;
	}

	#container {
		padding: 10px;
	}

	#title {
		font-weight: 300;
		font-size: 28px;
		color: #000;
	}

	#meta {
		font-size: 12px;
		margin-bottom: -15px;
	}

	.apt {
		text-align: center;
		background: #DDD;
		color: #000;
		font: 800 22px 'Open Sans', 'Arial Black', sans-serif;
		margin: .5em 0 .5em;
		text-transform: uppercase;
	}

	.apt-group {
		page-break-inside: avoid;
	}

	.member-block {
		display: inline;
		float: left;
		width: 24%;
		margin-left: 1%;
		
		page-break-inside: avoid;

		white-space: nowrap;
		overflow: hidden;
		font: 11px 'Open Sans', sans-serif;
		line-height: 1.5em;
		margin-bottom: 1em;
	}

	.pic-container {
		max-height: 1.5in;
		text-align: center;
		margin-bottom: .5em;
	}

	.profilePicture {
		max-width: 100%;
		max-height: 100%;
	}

	.name {
		font-size: 16px;
		font-weight: 600;
		color: #000;
		margin-bottom: .5em;
		text-align: center;
	}
	</style>
</head>
<body>
	<div id="container">
		
		<div id="header">
			<div id="title"><?php echo $WARD->Name; ?> Ward</div>
			<div id="meta">
				According to <b><?php echo SITE_DOMAIN; ?></b>
				as of <b><?php echo date("F j, Y"); ?></b>
			</div>
		</div>
		<hr class="line" style="margin-bottom: -.5em">
				
		<?php
			while ($r = mysql_fetch_array($q)):
				$mem = Member::Load($r['ID']);

				// Because of the epic SQL query above, regular addresses have both
				// a full address AND a "regular" one e.g. ("Stratford 203")
				// Prefer the "regular" one over the full one.
				$addrString = trim($r['RegularAddr']) ? $r['RegularAddr'] : $r['FullAddr'];

				if ($addrString == "")
					$addrString = "(No address provided)";

				// Get parts of the birth date (don't show year on printed directories)
				$bdate = strtotime($mem->Birthday);
				$mm = date("F", $bdate);
				$dd = date("j", $bdate);

				if ($lastApt != $addrString):
					$i = 0; // Reset the counter b/c we're restarting at a new row
		?>
			<hr class="clear">
		<?php if ($lastApt != "") echo '</div>';	/* (closes the .apt-group container)*/ ?>
			<div class="apt-group">
				<div class="apt">
					<?php echo $addrString; ?>
				</div>
		<?php
			$lastApt = $addrString;
			endif;
		?>
				<div class="member-block">
					<div class="pic-container">
						<img src="<?php echo $mem->PictureFile(false); ?>" class="profilePicture">
					</div>
					<div class="name">
						<?php echo $mem->FirstName(); ?>
						<?php echo $mem->LastName; ?>
					</div>

				<?php if (!$mem->HidePhone && $mem->PhoneNumber): ?>
					<i class="icon-phone"></i>&nbsp;&nbsp;<?php echo formatPhoneForDisplay($mem->PhoneNumber); ?><br>
				<?php endif; if (!$mem->HideEmail): ?>
					<i class="icon-envelope-alt"></i>&nbsp;<?php echo $mem->Email; ?><br>
				<?php endif; if (!$mem->HideBirthday): ?>
					<i class="icon-gift"></i>&nbsp;&nbsp;<?php echo "{$mm} {$dd}"; ?>
				<?php endif; ?>
				</div>
			
			<?php $i++; if ($i % 4 == 0) echo '<hr class="clear">'; ?>

		<?php endwhile; ?>
				</div> <!-- closes the last .apt-group container -->
		
		</div>
	
<script>
$(window).load(function()
{
	window.print();
});
</script>
	</body>
</html>