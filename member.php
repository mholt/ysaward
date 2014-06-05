<?php
require_once "lib/init.php";
protectPage(0, true);

if (!isset($_GET['id']))
	header("Location: /directory");

$mem = Member::Load($_GET['id']);

// No member with given ID number, or member is not in the same ward
if (!$mem || $mem->WardID != $WARD->ID())
	header("Location: /directory");

$isCurrent = $MEMBER && $MEMBER->ID() == $mem->ID();

// Get parts of the birth date
$bdate = strtotime($mem->Birthday);
$mm = date("F", $bdate);
$dd = date("j", $bdate);
$ordinal = date("S", $bdate);

// Load survey questions in order to get the answers
$r = DB::Run("SELECT ID FROM SurveyQuestions WHERE WardID='{$mem->WardID}' AND Visible='1'");
if (!$r)
	die("ERROR > Can't render this page because of a database problem. Please report this: ".mysql_error());

?>
<!DOCTYPE html>
<html>
	<head>
		<title><?php echo $mem->FirstName().' '.$mem->LastName; ?> &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
		<?php include "includes/head.php"; ?>
		<style>
		.whitebg {
			background: #FFF;
			padding: 2em 0;
		}

		.member-name {
			margin: 20px auto 20px;
			font-size: 72px;
			font-weight: 300;
			line-height: 1em;
			color: #000;
			text-align: center;
		}

		.member-meta {
			line-height: 2em;
			text-align: center;
		}

		.member-meta .metafield {
			display: inline-block;
			margin-right: 25px;
			white-space: nowrap;
			font-size: 18px;
		}

		.member-meta .metafield:last-child {
			margin-right: 0;
		}

		.member-meta .metafield a {
			color: inherit;
		}

		.member-meta .metafield a:hover {
			color: #000;
			text-decoration: none;
		}

		.member-meta .home {
			font-size: 22px;
			color: #000;
			text-align: center;
			display: block;
			font-weight: bold;
		}

		.misc {
			font-size: 14px;
			line-height: 1.75em;
		}

		.misc b {
			display: block;
			color: #000;
		}

		.chunk {
			display: block;
			margin-bottom: 2em;
		}

		.question,
		.answer {
			font-size: 16px;
		}

		.question {
			margin-bottom: .25em;
			border-bottom: 1px solid #AAA;
			background: #F3F3F3;
			padding: 5px;
			font-weight: bold;
		}

		.answer {
			margin-bottom: 2.5em;
		}

		.deletemem {
			position: relative;
			top: -1.5em;
			font-size: 12px;
			text-align: center;
		}

		.deletemem a {
			text-decoration: none;
		}

		@media (max-width: 767px) {
			.member-meta .metafield {
				display: block;
				text-align: left;
				padding-left: 25%;
			}
		}

		@media (max-width: 600px) {
			.member-name {
				margin: 10px auto 15px;
				font-size: 42px;
			}

			.member-meta .metafield {
				text-align: left;
				padding-left: 5%;
			}

			.member-meta .home {
				font-size: 20px;
			}

			mark {
				font-size: 12px;
			}
		}

		@media (max-width: 1024px) and (min-width: 768px) {
			.chunk {
				display: inline-block;
				margin-right: 3em;
				margin-bottom: 4em;
			}

			.misc {
				text-align: center;
			}
		}
		</style>
	</head>
	<body>
		<?php include "includes/header.php"; ?>

		<div class="whitebg">
			<div class="grid-container">

				<div class="grid-30 text-center">

					<?php if ($mem->PictureFile): ?>
						<a href="/uploads/<?php echo $mem->PictureFile; ?>" style="cursor: -moz-zoom-in; cursor: -webkit-zoom-in;" target="_blank"><?php echo $mem->ProfilePicImgTag(false, false); ?></a>
					<?php else: ?>
						<?php echo $mem->ProfilePicImgTag(false, false); ?>
					<?php endif; ?>
					
					<?php if ($isCurrent): ?>
					<small>
						<br>[<a href="/profile#pic">Change picture</a>]
					</small>
					<?php endif; ?>

				</div>

				<div class="grid-70">
					<?php if ($isCurrent): ?>
						<p class="text-center">
							<mark>
								This is you. <b><a href="/profile">Edit profile</a></b>
								| <b><a href="/survey">Edit survey answers</a></b>
							</mark>
						</p>
					<?php endif; ?>

					<div class="member-name">
						<?php echo $mem->FirstName().' '.$mem->LastName; ?>
					</div>

					<?php if ($MEMBER && $MEMBER->HasPrivilege(PRIV_DELETE_ACCTS) && $MEMBER->ID() != $mem->ID()): ?>
						<div class="deletemem">
							<a id="delmem" class="del" href="javascript:" title="Permanently delete this member's account"><i class="fa fa-times"></i> &nbsp;Delete Account</a>
						</div>
					<?php endif; ?>

					<div class="member-meta">
						<span class="home">
							<i class="fa fa-home"></i>
							<?php echo $mem->ResidenceString(); ?>
						</span>

						<hr>

						<?php if (!$mem->HideEmail || $isCurrent || $LEADER): ?>
						<span class="metafield">
							<a href="mailto:<?php echo $mem->Email; ?>" target="_blank" title="Send email">
								<i class="fa fa-envelope"></i>
								<?php echo $mem->Email; ?>
							</a>
						</span>
						<?php endif; ?>
						<?php if ($mem->PhoneNumber && (!$mem->HidePhone || $isCurrent || $LEADER)): ?>
						<span class="metafield">
							<?php if (IS_MOBILE) echo '<a href="tel:'.$mem->PhoneNumber.'">'; ?>
							<i class="fa fa-phone"></i>
							<?php echo formatPhoneForDisplay($mem->PhoneNumber); ?>
							<?php if (IS_MOBILE) echo '</a>'; ?>
						</span>
						<?php endif; ?>
						<?php if (!$mem->HideBirthday || $isCurrent || $LEADER): ?>
						<span class="metafield">
							<i class="fa fa-gift"></i>
							<?php echo "{$mm} {$dd}<sup>{$ordinal}</sup>"; ?>
						</span>
						<?php endif; ?>
					</div>

				</div>

				<hr class="clear">
				<br><br>


				<div class="grid-25 mobile-grid-30 misc">
					<div class="chunk">
						<?php
							$roommates = $mem->Roommates();
							echo count($roommates) == 1 ? '<b>Roommate</b>' : '<b>Roommates</b>';
							foreach ($roommates as $rm)
								echo '<a href="/member?id='.$rm->ID().'">'.$rm->FirstName()." ".$rm->LastName."</a><br>";
						?>
					</div>
					<div class="chunk">
						<?php
							$callings = $mem->Callings();
							echo count($callings) == 1 ? '<b>Calling</b>' : '<b>Callings</b>';
							foreach ($callings as $c)
								echo $c->Name."<br>";
						?>
					</div>

					<div class="chunk">
						<b>FHE group</b>
						<?php
							$group = FheGroup::Load($mem->FheGroup);
							if ($group)
								echo $group->GroupName;
						?>
					</div>

					<div class="chunk">
						<b>Member since</b>
						<?php echo date('F Y', strtotime($mem->RegistrationDate())); ?>
					</div>

					<div class="chunk">
						<b>Profile updated</b>
						<?php echo $mem->LastUpdated() > 0 ? date('j M Y', strtotime($mem->LastUpdated())) : "<i>Never</i>"; ?>
					</div>
					<br>

				</div>

				<div class="grid-75 mobile-grid-70">
<?php
if ($isCurrent):

	while ($row = mysql_fetch_array($r)):
		$qu = SurveyQuestion::Load($row['ID']);
		if (!$qu)
			continue;
		$ans = $qu->Answers($mem->ID());
		$readonlyAnswer = $ans && $ans->ReadonlyAnswer() ? $ans->ReadonlyAnswer() : '<i>no answer</i>';
?>
					<div class="question">
						<?php echo $qu->Question; ?>
					</div>
					<div class="answer">
						<?php echo $readonlyAnswer; ?>
					</div>
<?php
	endwhile;

else:
	// Get this person's answers to the survey, and display those which
	// the current member is allowed to see.
	$permissions = $USER->Permissions(true);
	
	if (count($permissions) == 0)
		echo "<i>None of {$mem->FirstName()}'s survey answers are available for you to view.</i>";

	foreach ($permissions as $per):
		$qu = SurveyQuestion::Load($per->QuestionID());
		if (!$qu)
			continue;
		$ans = $qu->Answers($mem->ID());
		$readonlyAnswer = $ans && $ans->ReadonlyAnswer() ? $ans->ReadonlyAnswer() : '<i>no answer</i>';

?>
					<div class="question">
						<?php echo $qu->Question; ?>
					</div>
					<div class="answer">
						<?php echo $readonlyAnswer; ?>
					</div>
<?php
	endforeach;
endif;
?>
				</div>


			</div>
		</div>

		<?php include "includes/footer.php"; ?>
		<?php include "includes/nav.php"; ?>


<script>
$('#delmem').click(function()
{
	var conf = confirm("Everything about this person will be deleted permanently from this website. It CANNOT be undone. Are you sure you want to do this?");
	if (conf == true)
		window.location = '/manage/api/deletemem?id=<?php echo($mem->ID()); ?>';
	else
		return;
});
</script>

	</body>
</html>