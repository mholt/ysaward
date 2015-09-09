<?php
protectPage(0, true);

$IS_STAKE_VIEW = $MEMBER == null && $LEADER != null && array_key_exists('stake', $_GET);

if ($IS_STAKE_VIEW) {
	// Get a list of all stake members
	$q = "SELECT ID FROM Members WHERE WardID IN (SELECT ID FROM Wards WHERE StakeID = '{$LEADER->StakeID}') ORDER BY FirstName ASC, LastName ASC";
} else {
	// Get a list of all ward members
	$q = "SELECT ID FROM Members WHERE WardID='{$WARD->ID()}' ORDER BY FirstName ASC, LastName ASC";
}

$r = DB::Run($q);
$memberCount = mysql_num_rows($r);

?>
<!DOCTYPE html>
<html>
	<head>
		<title><?= $IS_STAKE_VIEW ? 'Stake ' : '' ?>Directory &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
		<?php include "includes/head.php"; ?>
		<script src="/resources/js/directory_filter.js"></script>
		<style>
			.list > a {
				padding: 0;
				display: block;
				color: #505050;
				font-weight: 300;
				text-decoration: none;
				white-space: nowrap;
				overflow: hidden;
			}

			.list a.male {
				background: #F2F8FF;
			}

			.list a.female {
				background: #F9EFEF;
			}

			.list > a:hover,
			.list > a:active {
				background: #7A7A88;
				color: #FFF;
			}

			.list > a:hover .apt,
			.list > a:active .apt {
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

			.apt {
				display: inline-block;
				font-size: 10px;
				margin-left: .5em;
				color: #888;
				font-style: italic;
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
					<?php echo $memb->ProfilePicImgTag(true, true, "75px"); ?>
					<?php echo $memb->FirstName().' '.$memb->LastName; ?>
					<span class="apt"><?php echo $memb->ResidenceString(); ?></span>
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