<?php
protectPage(0, true);

// Get a list of all ward members
$q = "SELECT ID FROM Members WHERE WardID='{$WARD->ID()}' ORDER BY FirstName ASC, LastName ASC";
$r = DB::Run($q);
$memberCount = mysql_num_rows($r);

/*
 This user's privileges (extra info they can see, privately)
 CURRENTLY *NOT* USED FOR DISPLAYING THE DIRECTORY (in this file)
 ... because technically the privilege only applies to the
 export file, which does use these. However, the code
 is in place below, but is commented out, to support
 this feature in the online directory. (Would it also
 apply to members' profile pages then? The privilege's wording
 in the database/schema.sql file may have to be changed)
*/
//$allEmails = $MEMBER ? $MEMBER->HasPrivilege(PRIV_EXPORT_EMAIL) : true;	// Stake leaders should have it by default
//$allPhones = $MEMBER ? $MEMBER->HasPrivilege(PRIV_EXPORT_PHONE) : true;
//$allBdays = $MEMBER ? $MEMBER->HasPrivilege(PRIV_EXPORT_BDATE) : true;

// Get a list of the questions this person is allowed to see
$permissions = $USER->Permissions(true);

// Array of SurveyQuestion objects which this user can see
$questions = array(); // (populating in a moment...)

// Build questions array and build table header based on permissions
$th = '';
foreach ($permissions as $per)
{
	$question = SurveyQuestion::Load($per->QuestionID());
	$questions[] = $question;
	$th .= "<th>".$question->Question."</th>\r\n";
}

// Show "Ctrl" (non-Mac) or "Command" (Mac)
$cKey = stripos($_SERVER['HTTP_USER_AGENT'], "Macintosh") === false ? "Ctrl" : "command";


?>
<!DOCTYPE html>
<html>
	<head>
		<title>Directory &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
		<?php include "includes/head.php"; ?>
		<script src="/resources/js/directory_filter.js"></script>
		<style>
			#content {
				padding: 0;
			}

			table {
				border-collapse: collapse;
				min-width: 100%;
				position: relative;
				table-layout: fixed;
			}

			tbody td,
			thead th {
				background: #FFF;
				border: 1px solid #DDD;
				font-size: 14px;
				padding: 5px;
				vertical-align: middle;
			}

			thead th {
				font-weight: 800;
				text-transform: uppercase;
				line-height: 1.25em;
				background: #F5F5F5;
			}

			tbody td {
				max-height: 100px;
				height: 100px;
				line-height: 1.4em;
			}

			tbody td > div {
				max-height: 100px;
				overflow: hidden;
			}

			tbody tr:first-child td {
				border-top: none;
			}

			#table-body {
				position: relative;
				z-index: 2;
			}

			#table-body tr:hover td {
				background: #FFFFEE;
			}

			.name-field a {
				text-decoration: none;
				display: block;
				font-size: 18px;
			}

			.stay {
				background: #F8F8F8;
			}

			.profilePicture {
				width: <?php echo Member::THUMB_DIM; ?>px !important;
			}

			.vert-align-middle {
				display: inline-block;
				height: 100%;
				vertical-align: middle;
			}

			#filtering {
				font-size: 14px;
				margin-bottom: 1.5em;
				text-align: center;
			}

			#help-text {
				font-size: 12px;
			}
		</style>
	</head>
	<body>
		<div id="content">
			<div class="stay">
				<?php include "includes/header.php"; ?>

				<div id="filtering">
					Showing <b><span id="count"><?php echo $memberCount ?></span></b>
					<input type="checkbox" data-label="guys" id="show-guys" checked>
					<input type="checkbox" data-label="ladies" id="show-girls" checked>
					<div id="help-text">
						To search, press <kbd><?php echo $cKey; ?></kbd>+<kbd>F</kbd> and type
						what you're looking for.
					</div>
				</div>

				<table id="table-header">
					<thead>
						<tr>
							<th>Name</th>
							<th>Apartment</th>
							<th>Phone Number</th>
							<th>Email Address</th>
							<th>Birthday</th>
							<?php echo $th; /* Additional info this user has permission to see */  ?>
						</tr>
					</thead>
				</table>
			</div>
			
			<table id="table-body">
				<tbody>
				<?php
					while ($row = mysql_fetch_array($r)):
						$memb = Member::Load($row['ID']);

						// Get parts of the birth date (don't show year, by default)
						$bdate = strtotime($memb->Birthday);
						$mm = date("F", $bdate);
						$dd = date("j", $bdate);
						$ordinal = date("S", $bdate);
						$yyyy = date("Y", $bdate);
				?>
					<tr id="<?php echo $memb->ID(); ?>" class="<?php echo $memb->Gender == Gender::Male ? 'male' : 'female'; ?>">
						<td class="name-field nowrap">
							<div>
								<a href="member?id=<?php echo($memb->ID()); ?>" title="View profile">
									<?php echo $memb->ProfilePicImgTag(true); ?>
									<?php echo $memb->FirstName().' '.$memb->LastName; ?>
								</a>
							</div>
						</td>
						<td>
					 		<div>
					 			<div class="vert-align-middle">
					 				<?php echo $memb->ResidenceString(); ?>
					 			</div>
					 		</div>
					 	</td>
						<td><?php echo !$memb->HidePhone/* || $allPhones */ || $LEADER ? formatPhoneForDisplay($memb->PhoneNumber) : ''; ?></td>
						<td><?php echo !$memb->HideEmail/* || $allEmails*/ || $LEADER ? $memb->Email : ''; ?></td>
						<td><?php echo !$memb->HideBirthday/* || $allBdays*/ || $LEADER ? "{$mm} {$dd}<sup>{$ordinal}</sup>" : ''; if (/*$allBdays ||*/ $LEADER) echo ', '.$yyyy; ?></td>
				<?php
						// Display the members' answers this user is allowed to see
						foreach ($questions as $question):
							$ans = $question->Answers($memb->ID());
				?>
						<td>
							<div>
					 			<div class="vert-align-middle">
									<?php echo $ans ? $ans->ReadonlyAnswer() : ''; ?>
								</div>
							</div>
						</td>
				<?php
						endforeach;
				?>
					</tr>
				<?php
					endwhile;
				?>
				</tbody>
			</table>

			<?php include("includes/footer.php"); ?>

		</div>

		<?php include "includes/nav.php"; ?>


<script>
$(function()
{
	var tblHeader = $('#table-header');
	var tblBody = $('#table-body');
	var tblHeaderInitialBottom = tblHeader.offset().top + tblHeader.outerHeight();

	$(window).scroll(function()
	{
		// Keep the header row in sync with the rest of the table
		tblHeader.css('left', '-'+$(this).scrollLeft()+'px');
	});

	$(window).resize(function()
	{
		// Allow the table to freely resize and sync instead of being
		// bound to a minimum at its initial width, then sync the widths
		$('thead th, tbody td').css({
			'width': '',
			'min-width': '',
			'max-width': ''
		});
		syncTables();
	});

	// When showing/hiding rows, it's important to re-sync the tables
	$('#show-girls, #show-guys').change(syncTables);

	// Sync tables at page load
	syncTables();

	function syncTables()
	{
		// Set the widths of the headers to be the same as their column below them
		// To do this, loop through each header cell (th) and see if it or its
		// table cells below it have the wider width. Keep the wider of the two.
		$('thead th').each(function(idx, elem)
		{
			var associatedTableCells = $('tbody tr:visible td:nth-child('+(idx+1)+')');
			var newWidth = Math.max(
					parseInt(associatedTableCells.width(), 10),
					parseInt($(this).width(), 10)
				) + 1;
			$(this).add(associatedTableCells).css({
				'min-width': newWidth,
				'max-width': newWidth
			});
		});

		// In case a header row text wraps (likely), this keeps the table with the content
		// so it doesn't start from underneath the fold of the header row...
		tblBody.css('top', tblHeader.offset().top + tblHeader.outerHeight() - tblHeaderInitialBottom);
	}
});
</script>

	</body>
</html>