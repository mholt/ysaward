<?php
require_once "lib/init.php";
protectPage();

// Build list of callings and members who hold those callings
// to render it below.
$list = '';

$r = DB::Run("SELECT ID FROM Callings WHERE WardID={$MEMBER->WardID} ORDER BY Name ASC");

if (!$r)
	fail("ERROR > Could not request callings. Please report this: ".mysql_error());

$callings = array();

while ($row = mysql_fetch_array($r))
{
	$c = Calling::Load($row['ID']);

	if (!$c)
		continue;
	
	$r2 = DB::Run("SELECT MemberID FROM MembersCallings WHERE CallingID={$c->ID()}");
	
	if (!$r2)
		fail("ERROR > Can't list members' callings. Please report this: ".mysql_error());

	if (mysql_num_rows($r2) > 0)
	{
		$callings[$c->Name] = array();
		
		// Get a list of members with this calling
		while ($row2 = mysql_fetch_array($r2))
		{
			$m = Member::Load($row2['MemberID']);
			if (!$m)
				continue;
			$callings[$c->Name][] = $m;
		}
	}
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Callings &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
		<?php include "includes/head.php"; ?>
		<style>
		.calling-name {
			background: #EFEFEF;
			font-size: 14px;
			padding: 10px 15px;
			line-height: 1em;
			font-weight: bold;
		}
		</style>
	</head>
	<body>
		<?php include "includes/header.php"; ?>

		<h1>Callings</h1>

		<div class="grid-container">

<?php
foreach ($callings as $callingName => $members):
?>
			<div class="grid-25 mobile-grid-50">
				<div class="card">
					<div class="calling-name">
						<?php echo $callingName; ?>
					</div>
<?php
	foreach ($members as $mem):
?>
					<a href="/member?id=<?php echo $mem->ID(); ?>" class="member-link">
						<?php echo $mem->ProfilePicImgTag(true, true, 45); ?>
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
	</body>
</html>