<?php
protectPage(0, true);

// Get a list of all ward members
$q = "SELECT ID FROM Members WHERE WardID='{$WARD->ID()}' ORDER BY FirstName ASC, LastName ASC";
$r = DB::Run($q);
$memberCount = mysql_num_rows($r);

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Directory &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
		<?php include "includes/head.php"; ?>
		<script src="/resources/js/directory_filter.js"></script>
		<style>
			.list > a {
				padding: 0;
				display: block;
				color: #808080;
				font-weight: 300;
				text-decoration: none;
			}

			.list > a:nth-child(even) {
				background: #E6E6E6;
			}

			.list > a:nth-child(odd) {
				background: #EFEFEF;
			}

			.list > a:hover,
			.list > a:active {
				background: #7A7A88;
				color: #FFF;
			}

			.list > a img {
				margin-right: 20px;
				vertical-align: middle;
			}

			#filtering {
				font-size: 14px;
				margin-bottom: 1.5em;	
				text-align: center;
			}
		</style>
	</head>
	<body>
		<div id="content">

			<?php include "includes/header.php"; ?>

			<div id="filtering">
				Showing <b><span id="count"><?php echo $memberCount ?></span></b>
				<input type="checkbox" data-label="guys" id="show-guys" checked>
				<input type="checkbox" data-label="ladies" id="show-girls" checked>
			</div>


			<div class="list">
			<?php
				$i = 0;
				while ($row = mysql_fetch_array($r)):

					$memb = Member::Load($row['ID']);

				?>
				<a href="member?id=<?php echo($memb->ID()); ?>" class="<?php echo $memb->Gender == Gender::Male ? 'male' : 'female'; ?>">
					<?php echo $memb->ProfilePicImgTag(true, true, 75); ?>
					<?php echo $memb->FirstName().' '.$memb->LastName; ?>
				</a>
			<?php
				endwhile;
			?>
			</div>

			<?php include "includes/footer.php"; ?>

		</div>


		<?php include "includes/nav.php"; ?>


<script>
$(function()
{
	$('input[type=radio], input[type=checkbox]').prettyCheckable();
});
</script>

	</body>
</html>