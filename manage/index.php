<?php
require_once("../lib/init.php");
protectPage();

if (!$MEMBER->HasAnyManagePrivilege())
{
	header("Location: /directory.php");
	exit;
}

?>
<html>
<head>
	<title>Manage &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
	<?php include("../includes/head.php"); ?>
</head>
<body>
	
	<?php include("../includes/header.php"); ?>
	
	<article class="grid-12 group">
		
		<section class="g-12">
			
			<h1>Ward Management</h1>
			
			<ul>
				<?php if ($MEMBER->HasPrivilege(PRIV_MNG_FHE)): ?>
				<li>
					<b><a href="fhe.php">FHE Groups</a></b> - Create FHE groups and assign them leaders and members
					<br><br>
				</li>
				<?php endif; if ($MEMBER->HasPrivilege(PRIV_MNG_SURVEY_QU)): ?>
				<li>
					<b><a href="survey.php">Survey Questions</a></b> - Manage questions to the survey
					<br><br>
				</li>
				<?php endif; if ($MEMBER->HasPrivilege(PRIV_MNG_SURVEY_PER)): ?>
				<li>
					<b><a href="permissions.php">Survey Permissions</a></b> - Who can see answers to which survey questions
					<br><br>
				</li>
				<?php endif; if ($MEMBER->HasPrivilege(PRIV_MNG_SITE_PRIV)): ?>
				<li>
					<b><a href="privileges.php">Site Privileges</a></b> - Grant special site privileges to members or callings
					<br><br>
				</li>
				<?php endif; if ($MEMBER->HasPrivilege(PRIV_MNG_CALLINGS)): ?>
				<li>
					<b><a href="callings.php">Callings</a></b> - Create and assign callings to members
					<br><br>
				</li>
				<?php endif; if ($MEMBER->HasPrivilege(PRIV_MNG_PROFILE_PICS)): ?>
				<li>
					<b><a href="profilepictures.php">Profile pictures</a></b> - Manage members' profile pictures <i>en masse</i>
					<br><br>
				</li>
				<?php endif; if ($MEMBER->HasPrivilege(PRIV_DELETE_ACCTS)): ?>
				<li>
					<b><a href="prune.php">Old accounts</a></b> - Delete moved-out members <i>en masse</i>
					<br><br>
				</li>
				<?php endif; ?>
			</ul>

		</section>
		
	</article>
	
<?php include("../includes/footer.php"); ?>