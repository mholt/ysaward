<?php
require_once("lib/init.php");
protectPage(0, true);


if (!isset($_GET['id']))
	header("Location: /directory.php");

$mem = Member::Load($_GET['id']);

// No member with given ID number, or member is not in the same ward
if (!$mem || $mem->WardID != $WARD->ID())
	header("Location: /directory.php");


$isCurrent = $MEMBER && $MEMBER->ID() == $mem->ID();

// Get parts of the birth date
$bdate = strtotime($mem->Birthday);
$mm = date("F", $bdate);
$dd = date("j", $bdate);
$ordinal = date("S", $bdate);

// Profile picture filename
$profilePic = $mem->PictureFile();


$survey = '';
// These blocks are poorly designed, I know. (There's a
// lot of duplicated code in here...)
if ($isCurrent)
{
	// If a member is viewing his/her own profile,
	// show ALL their survey answers.
	$q = "SELECT ID FROM SurveyQuestions WHERE WardID='{$mem->WardID}' AND Visible='1'";
	$r = DB::Run($q);

	if (!$r)
		die("ERROR > Can't render this page because of a database problem. Please report this: ".mysql_error());

	while ($row = mysql_fetch_array($r))
	{
		$tr = "<h4>";
		$qu = SurveyQuestion::Load($row['ID']);
		if (!$qu)
		{
			$tr .= '</h4>\r\n';
			$trs.= $tr;
			continue;
		}
		$tr .= $qu->Question."</h4>\r\n\t\t\t\t";
		$ans = $qu->Answers($mem->ID());
		if ($ans)
			$ansDisplay = $ans->ReadonlyAnswer();
		else
			$ansDisplay = '<i>no answer</i>'; // No answer to this question
		$tr .= $ansDisplay."<br><br>\r\n";
		$survey .= $tr; // Add this question/answer to the rest of them
	}
}
else
{
	// Get this person's answers to the survey, and
	// display those which the current member is allowed
	// to see.
	$permissions = $USER->Permissions(true);
	foreach ($permissions as $per)
	{
		$tr = "<h4>";
		$qu = SurveyQuestion::Load($per->QuestionID());
		$tr .= $qu->Question."</h4>";
		$ans = $qu->Answers($mem->ID());
		if ($ans)
			$ansDisplay = $ans->ReadonlyAnswer();
		else
			$ansDisplay = '<i>no answer</i>'; // No answer to this question
		$tr .= $ansDisplay."<br><br>\r\n";
		$survey .= $tr; // Add this question/answer to the rest of them
	}
}


?>
<html>
<head>
	<title><?php echo $mem->FirstName().' '.$mem->LastName; ?> &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
	<?php include("includes/head.php"); ?>
	<style>

	.nameContainer {
		margin-bottom: 50px;
	}

	#picContainer img {
		box-shadow: 2px 2px 10px #AAA;
		border: 1px solid white;
	}

	.profile h2 {
		/*width: 50%;
		padding: 10px 0;
		border-bottom: 2px solid #333;
		text-align: center;*/
	}

	.profile h4 {
		margin-bottom: 5px;
		border-bottom: 1px solid #AAA;
		background: #F3F3F3;
		padding: 5px;
	}

	.left {
		width: 75%;
		min-width: 260px;
	}

	.name {
		font-size: 40px;
		line-height: 1.5em;
		margin-bottom: 15px;
	}
	</style>
</head>
<body>

	<?php include("includes/header.php"); ?>

	<article class="grid-12 group profile">
		<br>
		<section class="g-4 prefix-1">

			<!--<h2>Profile:
			<?php if ($isCurrent): ?>
				<span style="font-size: 14px; font-weight: normal;">[<a href="editprofile.php" title="Basic information shown below">edit</a>]</span>
			<?php endif; ?></h2>-->
			<div class="left">

				<div class="text-center" id="picContainer">
					<?php echo $mem->ProfilePicImgTag(false, false); ?>
					<br>
					<?php if ($isCurrent) echo '[<a href="editprofile.php#pic" title="Edit your profile">Change picture</a>]'; ?>
				</div><br>

				<h4>Apartment/address</h4>
				<?php echo $mem->ResidenceString(); ?>
				<br>

				<h4>Phone number</h4>
				<?php echo !$mem->HidePhone || $isCurrent || $LEADER ? formatPhoneForDisplay($mem->PhoneNumber) : ''; ?>
				<br>

				<h4>Email address</h4>
				<?php echo !$mem->HideEmail || $isCurrent || $LEADER ? $mem->Email : ''; ?>
				<br>

				<h4>Birthday</h4>
				<?php echo !$mem->HideBirthday || $isCurrent || $LEADER ? "{$mm} {$dd}<sup>{$ordinal}</sup>" : ''; ?>
				<br>

				<h4>Callings</h4>
				<?php
					$callings = $mem->Callings();
					foreach ($callings as $c)
						echo $c->Name."<br>";
					if (sizeof($callings) == 0)
						echo "<br>";
				?>

				<h4>FHE Group</h4>
				<?php
					$group = FheGroup::Load($mem->FheGroup);
					if ($group)
						echo $group->GroupName;
				?>
				<br><br>


				<?php if ($isCurrent): ?>
					<b><a href="editprofile.php" title="Just the basic information shown above">Edit Profile</a></b>
				<?php endif; ?>
			</div>
		</section>
		<section class="g-6 suffix-1">
			<div class="nameContainer">
				<h1 class="name text-left" style="margin-top: 0px;">
					<?php echo $mem->FirstName().' '.$mem->LastName; ?> <?php if ($isCurrent) echo '(you)'; ?>
				</h1>

				<?php if (!$isCurrent): ?><a href="/directory.php">&laquo; Back to directory</a><?php endif; ?>
				<?php if ($isCurrent): ?>
				<b><a href="/survey" title="The information shown below">Edit Survey Answers</a></b> &nbsp; or &nbsp; <b><a href="/profile" title="Basic information shown to the left">Edit Profile</a></b>
				<?php elseif ($MEMBER && $MEMBER->HasPrivilege(PRIV_DELETE_ACCTS)): ?>
				&nbsp;|&nbsp; <a id="delmem" href="javascript:" title="Permanently delete this member's account" style="font-weight: 400 !important;">Delete Member</a>
				<?php endif; ?>
			</div>


			<!--<h2>Survey answers: <?php if ($isCurrent): ?>
			<span style="font-size: 14px; font-weight: normal;">[<a href="/survey" title="Edit your survey answers">edit</a>]</span>
			<?php endif; ?></h2>-->

			<?php
				// Renders the questions/answers that the current user is allowed
				// to see about this member.
				if (strlen($survey) > 0)
					echo $survey;
				else
					echo "<i>None of {$mem->FirstName()}'s survey answers are available for you to view.</i>";
			?>
			<br>
			<?php if ($isCurrent): ?>
				<b><a href="/survey" title="The information shown above">Edit Survey Answers</a></b>
			<?php endif; ?>
		</section>
		<hr class="clear">

		<div class="text-center">
			<br><br><br>

			<a href="#">Back to top</a>
		</div>

	</article>

<script>

$('#delmem').click(function() {
	var conf = confirm("Everything about this person will be deleted permanently from this website. It CANNOT be undone. Are you sure you want to do this?");
	if (conf == true)
		window.location = 'manage/api/deletemem.php?id=<?php echo($mem->ID()); ?>';
	else
		return;
});

</script>

<?php include("includes/footer.php"); ?>