<?php
require_once("lib/init.php");
protectPage(0, true);

// The directory can take a while to load, so we show a
// loading message instead. We use ob ("output buffer") functions
// to tell PHP when to push down more info to the browser.
// Output buffering is very picky... it requires a certain
// size before being flushed, and flushing without a line break
// or tag or something other than a bit of text does't work.
// We flush once here, then at the end of this page where we
// suddenly show everything.
ob_start();

?>
<!DOCTYPE html>
	<!-- The DOCTYPE comes first, even here, otherwise IE goes into quirks mode... -->
	<div id="loading" style="padding: 20px; font-size: 18px; font-family: sans-serif;">
		<img src="images/loader1.gif" style="vertical-align: middle">
		&nbsp;
		Loading directory...
		<div style="font-size: 14px; color: #555; padding: 10px 40px;"><?php echo $WARD->Name; ?> Ward</div>
	</div>
<?php
echo str_repeat(' ', 1024 * 64);		// Fill buffer before flushing
ob_flush();

// Meanwhile... load the rest of the page.

// This user's privileges (extra info they can see, privately)
// CURRENTLY *NOT* USED FOR DISPLAYING THE DIRECTORY (in this file)
// ... because technically the privilege only applies to the
// export file, which does use these. However, the code
// is in place below, but is commented out, to support
// this feature in the online directory. (Would it also
// apply to members' profile pages then?)
$allEmails = $MEMBER ? $MEMBER->HasPrivilege(PRIV_EXPORT_EMAIL) : true;	// Stake leaders should have it by default
$allPhones = $MEMBER ? $MEMBER->HasPrivilege(PRIV_EXPORT_PHONE) : true;
$allBdays = $MEMBER ? $MEMBER->HasPrivilege(PRIV_EXPORT_BDATE) : true;


// Get a list of all ward members
$q = "SELECT ID FROM Members WHERE WardID='{$WARD->ID()}' ORDER BY FirstName ASC, LastName ASC";
$r = DB::Run($q);
$memberCount = mysql_num_rows($r);

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
<html style="display: none;"> <!-- It's important that only the HTML tag is hidden, not the body tag too -->
<head>
	<title>Directory &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
	<?php include("includes/head.php"); ?>
<style>
html, body {
	/* Critical to the effective function of this page */
	position: absolute;
	overflow: scroll;
}

#thContainer {
	width: 100%;
	overflow-x: hidden;
	position: relative;
	z-index: 100;
}

#memlistContainer {
	position: relative;
	z-index: 0;
	padding: 0px;
	width: 100%;
}

.memlist {
	border-collapse: collapse;
	border: 0px;
	font-size: 14px;
	position: relative;
	z-index: 100;
	min-width: 100%;
}

/* This effect almost seems annoying...

.memlist td,
.memlist td img {

	transition: height .15s linear, width .15s linear;
	-moz-transition: height .15s linear, width .15s linear;
	-webkit-transition: height .15s linear, width .15s linear;
	-o-transition: height .15s linear, width .15s linear;
}
*/

.memlist th,
.memlist td {
	padding: 0px 5px;
	min-width: 225px;
	border: 0px;
}

.memlist th {
	vertical-align: bottom;
	font-size: 12px;
	text-align: left;
	padding-bottom: 5px;
}

.memlist td {
	border-bottom: 1px solid #CCC;
	/*cursor: pointer;  ... To make it appear clickable, if desired */
	font-size: 12px;
	vertical-align: middle;
}

.memlist tr {
	border-bottom: 1px solid #777;
}

.memlist td img {
	width: 50px;
	height: 50px;
}

.memlist .memlink {
	font-size: 14px;
	display: block;
	padding: 20px 5px;
}

#trHeaders {
	background-color: white;
}

#instructions {
	margin: -40px 0 20px;
	text-align: center;
}

#fixed {
	position: fixed;
	top: 0;
	z-index: 100;
	background: #FFF;
	width: 100%;
}

.search {
	display: none;
}
</style>
</head>
<body>
	<div id="fixed">
		<?php include("includes/header.php"); ?>


		<div class="grid-12">

			<p class="g-12" id="instructions">
			
				<!-- Brief statistic -->
				Showing <b><span id="count"><?php echo $memberCount ?></span></b>
				<span id="countWhat">member<?php if ($memberCount != 1) echo "s"; ?></span>.
				
				&nbsp;
				
				<!-- Search tip -->
				<span id="defaultSearch" class="search">
					To search, press <kbd><?php echo $cKey; ?></kbd>+<kbd>F</kbd> and type
					what you're looking for.
				</span>
				<span id="mobileChromeSearch" class="search">
					To search, tap the menu icon, then "Find in page...".
				</span>
				<br>
				
				<!-- Filter -->
				<span style="font-size: 12px;">
					<b>Filter:</b> &nbsp;
					<label><input type="radio" id="showAll" name="filter" checked> All</label> &nbsp;
					<label><input type="radio" id="showGuys" name="filter"> Guys</label> &nbsp;
					<label><input type="radio" id="showGirls" name="filter"> Ladies</label>
				</span>
			</p>

		</div>

		<div id="thContainer">
			<table class="memlist">
				<tr id="trHeaders">
					<th style="width: 120px;"></th>
					<th>Name</th>
					<th>Apartment</th>
					<th>Phone Number</th>
					<th>Email Address</th>
					<th>Birthday</th>
					<?php echo $th; /* Additional info this user has permission to see */  ?>
				</tr>
			</table>
		</div>
	</div>


	<article>
		<div id="memlistContainer">
			<table class="memlist">
			<?php
				$i = 0;
				while ($row = mysql_fetch_array($r)):
					$memb = Member::Load($row['ID']);
					$rowColor = $i % 2 == 0 ? "#F6F6F6" : "#FFF";

					// Get parts of the birth date (don't show year, by default)
					$bdate = strtotime($memb->Birthday);
					$mm = date("F", $bdate);
					$dd = date("j", $bdate);
					$ordinal = date("S", $bdate);
					$yyyy = date("Y", $bdate);
			?>
				<tr id="<?php echo $memb->ID(); ?>" style="background-color: <?php echo $rowColor; ?>;" class="<?php echo $memb->Gender == Gender::Male ? 'male' : 'female'; ?>">
					<td style="width: 120px; text-align: center; padding-top: 5px;"><a href="member.php?id=<?php echo($memb->ID()); ?>" title="View profile"><?php echo $memb->ProfilePicImgTag(true); ?></a></td>
					<td style="white-space: nowrap;"><a href="member.php?id=<?php echo($memb->ID()); ?>" title="View profile" class="memlink<?php if ($MEMBER && $memb->ID() == $MEMBER->ID()) echo ' bold'; ?>"><?php echo $memb->FirstName().' '.$memb->LastName; ?></a></td>
					<td><?php echo $memb->ResidenceString(); ?></td>
					<td><?php echo !$memb->HidePhone/* || $allPhones */ || $LEADER ? formatPhoneForDisplay($memb->PhoneNumber) : ''; ?></td>
					<td><?php echo !$memb->HideEmail/* || $allEmails*/ || $LEADER ? $memb->Email : ''; ?></td>
					<td><?php echo !$memb->HideBirthday/* || $allBdays*/ || $LEADER ? "{$mm} {$dd}<sup>{$ordinal}</sup>" : ''; if (/*$allBdays ||*/ $LEADER) echo ', '.$yyyy; ?></td>
			<?php
					// Display the members' answers this user is allowed to see
					foreach ($questions as $question):
						$ans = $question->Answers($memb->ID());
			?>
					<td><?php echo $ans ? $ans->ReadonlyAnswer() : ''; ?></td>
			<?php
					endforeach;
			?>
				</tr>
			<?php
					$i++;
				endwhile;
			?>
			</table>
			<br>



			<div class="grid-12">
				<p class="g-12">
					<a href="#">Back to top</a>
				</p>

				<?php include("includes/footer.php"); ?>
			</div>

		</div>

	</article>


<script type="text/javascript">
var ths, tbl;

$(function() {

	// Hide the loading text and show the page when the buffer's been flushed to this point.
	$('#loading').remove();	// Leaving anything above the DOCTYPE kicks IE into quirks mode.
	$('html').show();		// It's vital that only the html tag is hidden, not the body tag

	ths = $('.memlist').first();	// Header row
	tbl = $('.memlist').last();		// Table containing the directory


	// Don't apply hover effects on mobile devices, for performance reasons
	if (mobile)
		$('head').append('<style>.memlist td img { width: 100px !important; height: 100px !important; } .memlist .memlink { padding: 30px; 5px; }</style>');
	else
		$('head').append('<style>.memlist tr:hover td { background: #FFFBCA !important; border-bottom: 2px solid #BBB; height: 100px; min-height: 100px; }'
					+ '.memlist tr:hover img { width: 100px; height: 100px; } .memlist tr:hover .memlink { padding: 30px 5px; }</style>');


	// Show search instructions depending on platform (these defined in resources/script.js)
	if (mobile && isChrome)
		$('#mobileChromeSearch').show();
	else if (!isIOS)
		$('#defaultSearch').show();

	positionTop();


	if (mobile)
	{
		// Resolves some pinch-zoom issues
		$(window).resize(function() {
			positionTop();
		});
	}

	$(window).scroll(function() {
		if (!mobile)
		{
			// Again, for performance reasons, we don't do this on mobile
			if ($(this).scrollTop() > 10)
				$('#thContainer').css('box-shadow', '0px 5px 6px -2px rgba(0, 0, 0, .2)');
			else
				$('#thContainer').css('box-shadow', 'none');
		}

		// Keep the header row in sync with the rest of the table
		ths.parent().scrollLeft($(this).scrollLeft());
	});

	
	// Set the widths of the headers to be the same as their column below them
	// To do this, loop through each header cell (th) and see if it or its
	// table cells below it have the wider width. Keep the wider of the two.
	$('#trHeaders th').each(function(idx, elem) {
		var associatedTableCells = $('tr td:nth-child('+(idx+1)+')', tbl);
		var minWidth = parseInt($(this).css('min-width'), 10);
		var newWidth = Math.max(associatedTableCells.width(), minWidth);
		$(this).width(newWidth).css('min-width', newWidth + "px");
		associatedTableCells.width(newWidth).css('min-width', newWidth + "px");
	});

	var males = $('.male'), females = $('.female');
	var allCount = $('#count').text();
	var maleCount = males.length;
	var femaleCount = females.length;


	/** Directory filters: show guys or girls or all **/

	// Show everyone
	$('#showAll').click(function()
	{
		if ($(this).is(':checked'))
		{
			males.show();
			females.show();
			$('#count').text(allCount);
			$('#countWhat').text(allCount != 1 ? "members" : "member");
		}
	});

	// Show guys
	$('#showGuys').click(function()
	{
		if ($(this).is(':checked'))
		{
			males.show();
			females.hide();
			$('#count').text(maleCount);
			$('#countWhat').text(maleCount != 1 ? "guys" : "guy");
		}
	});

	// Show girls
	$('#showGirls').click(function()
	{
		if ($(this).is(':checked'))
		{
			males.hide();
			females.show();
			$('#count').text(femaleCount);
			$('#countWhat').text(femaleCount != 1 ? "ladies" : "lady");
		}
	});

	// Make sure all the visible pictures load; this makes sure that happens (with lazy loading)
	$('#showAll, #showGuys, #showGirls').click(function() {
		$(document).scroll();
	});

	/** END directory filters **/


	function positionTop()
	{
		// Position the directory just below the header area
		$('article').css('margin-top', $('#fixed').height() + "px");
	}

});
</script>

</body>
</html>
<?php ob_flush(); ob_end_clean(); ?>