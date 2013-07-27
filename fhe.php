<?php
require_once("lib/init.php");
protectPage();

$mGroup = $MEMBER->FheGroup();

// Get a list of all members of the ward by FHE group
$mems = array();
$q = "SELECT ID FROM Members WHERE WardID='$MEMBER->WardID' AND FheGroup > 0 ORDER BY FheGroup,FirstName,LastName ASC";
$r = DB::Run($q);
while ($row = mysql_fetch_array($r))
	array_push($mems, Member::Load($row['ID']));



function renderMember($mem, $leader = false)
{
?>
	<a href="member.php?id=<?php echo $mem->ID(); ?>"<?php if ($leader) echo ' class="bold"'; ?>>
		<?php echo $mem->ProfilePicImgTag(true, true, 35); ?>
		&nbsp;
		<?php echo $mem->FirstName().' '.$mem->LastName; ?>
		<?php if ($leader): ?>&nbsp;<small class="caption not-bold">(leader)</small><?php endif; ?>
	</a>
<?php
}
?>
<html>
<head>
	<title>FHE Groups &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
	<?php include("includes/head.php"); ?>
<style>
table {
	margin: 0px auto;
}

td {
	vertical-align: top;
	min-width: 250px;
	float: left;
	width: 225px;
	padding-right: 25px;
	padding-bottom: 50px;
	font-size: 12px;
}

td a {
	display: block;
	padding: 5px;
}

td a:hover {
	text-decoration: none;
	background: #DDEEFF;
}

td a img {
	width: 35px;
	vertical-align: middle;
}

hr {
	border: 1px solid #AACCFF;
	margin-top: -15px;
	margin-bottom: 15px;
}

.leaders {
	margin-bottom: 15px;
}

.leaders b {
	font-size: 12px;
}
/*
Styles using divs instead of table:
.fhegrp { float: left; width: 250px; margin-right: 50px; margin-bottom: 50px; }
.fhegrp a { display: block; padding: 3px; margin-bottom: 5px; } .fhegrp a img { width: 35px; vertical-align: middle; }
*/
</style>
</head>
<body>
	
	<?php include("includes/header.php"); ?>
	
	<article class="grid-12 group">
		
		<section class="g-12">
			<h1>FHE Groups</h1>

			<?php if ($mGroup): ?>
			<p class="text-center"><mark>You are in FHE group: &nbsp; <b><?php echo $mGroup->GroupName; ?></b></mark></p>
			<?php endif; ?>

			<table>
				<?php
					$maxAcross = 3;		// How many groups should be shown side-by-side before wrapping (default 3)
					$current = NULL;	// Copy of the current FHE group
					$counter = 0;		// Group counter

					foreach ($mems as $mem)
					{

						if (!$current || $mem->FheGroup != $current->ID())
						{
							// Next group!
							$current = $mem->FheGroup();

							if ($counter > 0)
								echo "</td>";
							//	echo "</div>\r\n";

							if (!($counter % $maxAcross))
							{
								if ($counter > 0)
									echo "</tr>\r\n";
								echo "<tr>\r\n";
							}

							//echo "<td><h2 class=\"text-center\">{$current->GroupName}</h2><hr>\r\n";
							//echo '<div class="fhegrp"><h3>Group: '.$current->GroupName."</h3>\r\n";
							// If you toggle the above line to use <div>s instead of the table, don't forget to toggle the styles above!
					?>
							<td>
								<h2 class="text-center"><?php echo $current->GroupName; ?></h2>
								
								<div class="leaders">
									<!--<b>Leaders</b>-->
									<?php
										$ldr1 = Member::Load($current->Leader1);
										$ldr2 = Member::Load($current->Leader2);
										$ldr3 = Member::Load($current->Leader3);

										if ($ldr1)
											renderMember($ldr1, true);
										if ($ldr2)
											renderMember($ldr2, true);
										if ($ldr3)
											renderMember($ldr3, true);
									?>
								</div>
					<?php
							$counter ++;
						}

						if ($mem->ID() != $current->Leader1 && $mem->ID() != $current->Leader2 && $mem->ID() != $current->Leader3)
							renderMember($mem); // Display the member's name as a link to their profile (function is above)

						//echo "\t<a href=\"member.php?id={$mem->ID()}\"";
						//if ($current->Leader1 == $mem->ID() || $current->Leader2 == $mem->ID() || $current->Leader3 == $mem->ID())
						//	echo ' style="background-color: #D1F0FF;"';
						//echo '>';
						//echo $mem->ProfilePicImgTag(true, true, 35).'&nbsp; &nbsp;';
						//echo $mem->FirstName().' '.$mem->LastName."</a>\r\n";
					}
				?>
					</td>
				</tr>
			</table>
		</section>
		
	</article>
	
<?php include("includes/footer.php"); ?>