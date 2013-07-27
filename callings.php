<?php
require_once("lib/init.php");
protectPage();

// Build list of callings and members who hold those callings
// to render it below.
$list = '';

$q = "SELECT ID FROM Callings WHERE WardID={$MEMBER->WardID} ORDER BY Name ASC";
$r = DB::Run($q);

if (!$r)
	die("ERROR > Something happened; can't list callings. Please report this: ".mysql_error());

while ($row = mysql_fetch_array($r))
{
	$c = Calling::Load($row['ID']);
	if (!$c)
		continue;
	$list .= "<tr><td>{$c->Name}</td><td>";
	
	$q2 = "SELECT MemberID FROM MembersCallings WHERE CallingID={$c->ID()}";
	$r2 = DB::Run($q2);
	if (!$r2)
		die("ERROR > Something happened; can't list callings. Please report this: ".mysql_error());
	if (mysql_num_rows($r2) > 0)
	{
		$list .= "\r\n";
		
		// Get a list of members with each calling
		while ($row2 = mysql_fetch_array($r2))
		{
			$m = Member::Load($row2['MemberID']);
			if (!$m)
				continue;
			$list .= "<a href=\"member.php?id={$m->ID()}\" style='display: block;' title='View profile'>{$m->FirstName()} {$m->LastName}</a>\r\n";
		}
		$list .= "\r\n";
	}
	$list .= "</td></tr>\r\n";
}
?>
<html>
<head>
	<title>Callings &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
	<?php include("includes/head.php"); ?>
<style>

.callings {
	max-width: 750px;
	width: 80%;
	margin: 0px auto;
	border-collapse: collapse;
	font-size: 14px;
}

th, td {
	border-bottom: 1px solid #CCC;
	padding: 7px;
}

.callingHeader {
	width: 30%;
	min-width: 350px;
}
</style>
</head>
<body>
	
	<?php include("includes/header.php"); ?>
	
	<article class="grid-12 group">
		
		<section class="g-12">

			<h1>Callings</h1>

			<p class="text-center">Listed alphabetically by calling.
				<br>
				<i style="font-size: 14px;">
					Note: This may be incomplete and is not a replacement for the 
					official list of callings on <a href="https://www.lds.org/directory/#x" target="_blank">LDS.org</a>.
				</i>
			</p>
			
			<table class="callings">
				<tr>
					<th class="callingHeader">Calling</th>
					<th>Members</th>
				</tr>
				<?php echo $list; ?>
			</table>
			
			<br><br>
			<a href="#">Back to top</a>

		</section>
		
	</article>
	
<?php include("includes/footer.php"); ?>