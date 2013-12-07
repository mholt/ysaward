<?php
require_once "lib/init.php";
protectPage();

$mGroup = $MEMBER->FheGroup();

// Get a list of all members of the ward by FHE group
$mems = array();
$q = "SELECT ID FROM Members WHERE WardID='$MEMBER->WardID' AND FheGroup > 0 ORDER BY FheGroup,FirstName,LastName ASC";
$r = DB::Run($q);
while ($row = mysql_fetch_array($r))
	array_push($mems, Member::Load($row['ID']));

// Arrange the members, grouped by FHE group, into groups. (Huh?)
$groups = array();
foreach ($mems as $mem)
{
	$groupid = $mem->FheGroup;

	if (!array_key_exists($groupid, $groups))
	{
		$group = $mem->FheGroup();
		$groups[$groupid] = array();
		$groups[$groupid]['group'] = $group;
		$groups[$groupid]['leaders'] = array();
		$groups[$groupid]['members'] = array();

		$ldr1 = Member::Load($group->Leader1);
		$ldr2 = Member::Load($group->Leader2);
		$ldr3 = Member::Load($group->Leader3);
		
		if ($ldr1) $groups[$groupid]['leaders'][] = $ldr1;
		if ($ldr2) $groups[$groupid]['leaders'][] = $ldr2;
		if ($ldr3) $groups[$groupid]['leaders'][] = $ldr3;
	}

	// Only add the member to the regular member list if they're not a group leader
	$isLeader = false;
	foreach ($groups[$groupid]['leaders'] as $ldr)
	{
		if ($ldr->ID() == $mem->ID())
		{
			$isLeader = true;
			break;
		}
	}
	if (!$isLeader)
		$groups[$groupid]['members'][] = $mem;
}

?>
<!DOCTYPE html>
<html>
	<head>
		<title>FHE Groups &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
		<?php include "includes/head.php"; ?>
		<style>
		.group-name {
			padding: 10px 5px;
			background: #E0E0E0;
			text-align: center;
			font-weight: bold;
		}

		.grouping-header {
			font-size: 10px;
			line-height: 2em;
			padding: 0px 10px;
			text-transform: uppercase;
			background: #444;
			color: #FFF;
			font-weight: 600;
			border-bottom: 1px solid #AAA;
		}
		</style>
	</head>
	<body>
		<?php include "includes/header.php"; ?>

		<h1>FHE Groups</h1>

<?php if ($mGroup): ?>
		<p class="text-center">
			<mark>
				Your FHE group:
				&nbsp;
				<b><?php echo $mGroup->GroupName; ?></b>
			</mark>
		</p><br>
<?php endif; ?>


		<div class="grid-container">

<?php

foreach ($groups as $grp):
	$groupName = $grp['group']->GroupName;
	$leaders = $grp['leaders'];
	$members = $grp['members'];
	$leaderCount = count($leaders);
	$memberCount = count($members);
?>
			<div class="grid-25 mobile-grid-50 fhegroup">

				<div class="card">
					<div class="group-name">
						<?php echo $groupName; ?>
					</div>

					<div class="grouping-header">
						<?php echo $leaderCount; echo $leaderCount == 1 ? " Group Leader" : " Group Leaders"; ?>
					</div>
<?php
	foreach ($leaders as $ldr):
?>
					<a href="/member?id=<?php echo $ldr->ID(); ?>" class="member-link">
						<?php echo $ldr->ProfilePicImgTag(true, true, "45px"); ?>
						<?php echo $ldr->FirstName().' '.$ldr->LastName; ?>
					</a>
<?php
	endforeach;
?>
					<div class="grouping-header">
						<?php echo $memberCount; echo $memberCount == 1 ? " Member" : " Members"; ?>
					</div>

<?php
	foreach ($members as $mem):
?>
					<a href="/member?id=<?php echo $mem->ID(); ?>" class="member-link">
						<?php echo $mem->ProfilePicImgTag(true, true, "45px"); ?>
						<?php echo $mem->FirstName().' '.$mem->LastName; ?>
					</a>
<?php
	endforeach;
?>

				</div>

			</div>
<?php
endforeach;
?>
		</div>


		<?php include "includes/footer.php"; ?>
		<?php include "includes/nav.php"; ?>
		
<script>
$(function()
{
	// For FHE groups boxes of different heights,
	// these 'clear' divs will force them to fold
	// under each other properly. Mobile layout
	// only shows 2 FHE groups per row; desktop 4.
	// We employ a similar tactic on the Callings page.
	$('.fhegroup').each(function(i)
	{
		if ((i + 1) % 2 == 0)
			$(this).after('<div class="clear hide-on-desktop"></div>');
		if ((i + 1) % 4 == 0)
			$(this).after('<div class="clear hide-on-mobile"></div>');
	});
});
</script>
	</body>
</html>