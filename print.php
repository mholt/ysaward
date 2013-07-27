<?php
require_once("lib/init.php");
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
?>
<html>
<head>
	<title><?php echo $WARD->Name; ?> Ward Directory &mdash; <?php echo SITE_NAME; ?></title>
	<?php include("includes/head.php"); ?>
<style>

* {
	text-shadow: none !important;	/* Important for clear, non-blurry printing! */
}

#container {
	padding: 10px;
}

#title {
	font-weight: 800;
	font-size: 32px;
	text-align: left;
	color: #000;
	margin: 0 0 10px 0;
}

#meta {
	font-size: 12px;
	margin-left: 10px;
	position: relative;
	top: -5px;
}

img {
	/* Profile picture styles...? */
	max-width: 100px !important;
	max-height: 100px !important;
}

td {
	padding: 4px;
	font-size: 11px;
	font-family: sans-serif;

}

td.picTd {
	width: 5px;
}

.apt {
	font-family: 'Arial Black', sans-serif;
	font-weight: 800;
	font-size: 14px;
	margin: 20px 0 10px;
	text-transform: uppercase;
}

.name {
	font-size: 16px;
	font-weight: normal;
}

table.memberBlock {
	display: inline;
	float: left;
	width: 290px;
	margin-right: 10px;
}
</style>
</head>
<body>
	<div id="container">
		
		<span id="title"><?php echo $WARD->Name; ?> Ward</span>
		<span id="meta">(<?php echo date("d M Y"); ?>)</span>
		<hr>
				
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
				$ordinal = date("S", $bdate);
		?>
		
			<?php if ($lastApt != $addrString): $i = 0; /* Reset the counter b/c we're restarting at a new line */ ?>
			<hr class="clear">
			<div class="apt">
				<?php echo $addrString; ?>
			</div>
			<?php $lastApt = $addrString; endif; ?>
			
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

						<?php if (!$mem->HideEmail): /* Always honor these when printing a directory */ ?>
							<?php echo $mem->Email; ?><br>
						<?php endif; if (!$mem->HidePhone): ?>
							<?php echo formatPhoneForDisplay($mem->PhoneNumber); ?><br>
						<?php endif; if (!$mem->HideBirthday): ?>
							<b>Birthday:</b> <?php echo "{$mm} {$dd}<small>{$ordinal}</small>"; ?><br>
						<?php endif; ?>
					</td>
				</tr>
			</table>
			
			<?php $i++; if ($i % 2 == 0) echo '<hr class="clear">'; ?>
			
		<?php endwhile; ?>
		
	</div>
	
<script>
$(window).load(function() {
	window.print();
});
</script>
</body>
</html>