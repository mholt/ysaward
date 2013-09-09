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
	}

	#container {
		padding: 10px;
	}

	#header {
		text-align: center;
	}

	#title {
		font-weight: 300;
		font-size: 42px;
		color: #000;
		margin: 0 0 10px 0;
	}

	#meta {
		font-size: 12px;
		margin-bottom: -10px;
	}

	img {
		/* Profile picture styles...? */
		max-width: 100px !important;
		max-height: 100px !important;
	}

	td {
		padding: 4px;
		font: 11px 'Open Sans', sans-serif;
		vertical-align: top;
		line-height: 1.5em;
	}

	td.picTd {
		width: 100px;
		text-align: center;
	}

	.apt {
		color: #000;
		font: 800 22px 'Open Sans', 'Arial Black', sans-serif;
		margin: 1.5em 0 .5em;
		text-transform: uppercase;
	}

	.name {
		font-size: 16px;
		font-weight: 600;
		color: #000;
		margin-bottom: .8em;
	}

	table.memberBlock {
		display: inline;
		float: left;
		width: 290px;
		margin-right: 10px;
		page-break-inside: avoid;
	}

	.apt-group {
		page-break-inside: avoid;
	}
	</style>
</head>
<body>
	<div id="container">
		
		<div id="header">
			<div id="title"><?php echo $WARD->Name; ?> Ward</div>
			<div id="meta">As of <?php echo date("F j, Y"); ?></div>
		</div>
		<hr class="line">
				
		<?php
			while ($r = mysql_fetch_array($q)):
				$mem = Member::Load($r['ID']);

				// Because of the epic SQL query above, regular addresses have both
				// a full address AND a "regular" one e.g. ("Stratford 203")
				// Prefer the "regular" one over the full one.
				$addrString = trim($r['RegularAddr']) ? $r['RegularAddr'] : $r['FullAddr'];

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

				<table class="memberBlock">
					<tr>
						<td class="picTd">
							<?php echo $mem->ProfilePicImgTag(false, false, 100); ?>
						</td>
						<td class="info">
							<div class="name">
								<?php echo $mem->FirstName(); ?>
								<?php echo $mem->LastName; ?>
							</div>

							<?php if (!$mem->HideBirthday): ?>
								<b>Birthday:</b> <?php echo "{$mm} {$dd}"; ?><br>
							<?php endif; if (!$mem->HideEmail): ?>
								<?php echo $mem->Email; ?><br>
							<?php endif; if (!$mem->HidePhone && $mem->PhoneNumber): ?>
								<?php echo formatPhoneForDisplay($mem->PhoneNumber); ?>
							<?php endif; ?>
						</td>
					</tr>
				</table>
			
			<?php $i++; if ($i % 2 == 0) echo '<hr class="clear">'; ?>

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