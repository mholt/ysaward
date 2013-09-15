<?php
require_once "../lib/init.php";
protectPage(9);



// Get a list of all current ward members
$qm = "SELECT ID FROM Members WHERE WardID='$MEMBER->WardID' ORDER BY FirstName ASC, LastName ASC";
$rm = DB::Run($qm);

// Get a list of callings
$qc = "SELECT ID FROM Callings WHERE WardID='$MEMBER->WardID' ORDER BY Name ASC";
$rc = DB::Run($qc);

// Get a list of permissions. "AllPermissions" is a view in the database that simplifies rendering.
$qp = "SELECT * FROM AllPermissions WHERE WardID='$MEMBER->WardID' ORDER BY Name, Question";
$rp = DB::Run($qp);

// Get a list of survey questions
$qs = "SELECT ID FROM SurveyQuestions WHERE WardID='$MEMBER->WardID' ORDER BY ID ASC";
$rs = DB::Run($qs);

$maxLen = 70; // Maximum display length of a calling or question



?>
<!DOCTYPE html>
<html>
	<head>
		<title>Manage permissions &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
		<?php include "../includes/head.php"; ?>
		<style>
		.pers {
			display: none;
			font-size: 14px;
		}

		#selectMember {
			display: none;
		}

		select {
			min-width: 250px;
		}

		.questionList {
			padding: 10px 0 20px 0px;
		}

		.questionList label {
			display: block;
			font-size: 12px;
		}

		.existingPermissions table {
			 width: 100%;
			 border: 1px solid #AAA;
			 font-size: 14px;
		}

		.existingPermissions th {
			 min-width: 200px;
			 text-align: left;
			 padding: 0px;
		}

		.existingPermissions th .toggler {
			display: block;
			text-decoration: none;
			padding: 8px;
		}

		.existingPermissions th a:hover {
			background-color: #E1F7F7;
		}

		.existingPermissions td {
			width: 70px;
			text-align: right;
			padding-right: 5px;
			font-size: 12px;
		}

		.pers {
			padding: 5px 2px;
		}

		.permission {
			display: block;
		}

		.permission:hover a.delPer {
			visibility: visible;
		}

		a.delPer {
			font-size: 12px;
			padding: 0px 4px;
			visibility: hidden;
		}

		[type=submit] {
			font-size: 20px !important;
		}

		select {
			font-size: 16px;
		}
		</style>
	</head>
	<body>
		<?php include "../includes/header.php"; ?>

		<h1>Survey permissions</h1>

		<div class="grid-container">

			<div class="grid-100">

				<div class="instructions">
					<p>
						Use this page to define who can see which <a href="survey">survey questions</a> from
						members in the ward. Only grant permissions for callings or members <i>as needed</i>
						to respect the privacy of members, especially for any questions related to ordinances,
						temple recommends, relationships, home/previous-ward info, and skills or aptitudes.
					</p>

					<p>
						Please be conservative when granting permissions.
					</p>

					<p>
						While the survey should mainly be used as a utility for various callings, the questions may be fun, too.
						Some of these "fun"-type questions might be shared with the whole ward, for example: favorite scripture.
						<small><br><b>(There's not a way to share a question with the whole ward yet, but let me know if you want it.)</b></small>
					</p>

					
					<b>1.</b> On the left, add new permissions. &nbsp; &nbsp;
					<b>2.</b> On the right, view and delete existing permissions. (Click a name to expand the section.)
				</div>



				<div class="grid-50 addPermissions">
					<div class="wide padded card">
						<h2>Add new permission</h2>
					
						<form method="post" action="api/addpermission">
							<input type="radio" name="which" id="chooseCalling" data-label="Calling" checked>
							&nbsp;
							<input type="radio" name="which" id="chooseMember" data-label="Member">

							<br><br>

							<div id="selectMember">
								<select size="1" name="memberID">
									<option value="" selected="selected">Select member...</option>
<?php
	while ($row = mysql_fetch_array($rm)):
		$m = Member::Load($row['ID']);
?>
										<option value="<?php echo $m->ID(); ?>"><?php echo strip_tags($m->FirstName.' '.$m->LastName); ?></option>
<?php
	endwhile;
?>
								</select>
							</div>

							<div id="selectCalling">
								<select size="1" name="callingID">
									<option value="" selected="selected">Select calling...</option>
<?php
	while ($row = mysql_fetch_array($rc)):
		$c = Calling::Load($row['ID']);
		$name = strlen($c->Name) > $maxLen ? substr($c->Name, 0, $maxLen).'...' : $c->Name;
?>
									<option value="<?php echo $c->ID(); ?>"><?php echo strip_tags($name); ?></option>
<?php
	endwhile;
?>
								</select>
							</div>

							<b>Can see answers to:</b>

							<div class="questionList">
								<label><input type="checkbox" class="standard" id="selectAll"> <i>Select All</i></label>
<?php
	while ($row = mysql_fetch_array($rs)):
		$sq = SurveyQuestion::Load($row['ID']);
		$question = strlen($sq->Question) > $maxLen ? substr($sq->Question, 0, $maxLen).'...' : $sq->Question;
?>
								<label><input type="checkbox" name="questionID[]" value="<?php echo $sq->ID(); ?>" class="standard" id="question-id-<?php echo $sq->ID(); ?>"> <?php echo strip_tags($question); ?></label>
<?php
	endwhile;
?>
							</div>
							<input type="submit" class="button sm" value="Grant permissions">
						</form>
					</div>
				</div>


				<div class="grid-50 existingPermissions">

					<div class="wide padded card">

						<h2>Existing permissions</h2>
					
						<!--<p>Sorted alphabetically by member or calling names.
						The permissions granted to each one are listed below
						each calling or member name.</p>
						
						<p>To expand a section, click on the member or calling
						name to show the permissions.</p>-->
				
<?php

$lastObjID = 0;
$lastObjName = "";

while ($row = mysql_fetch_array($rp)):
	$perID = $row['ID'];
	$objID = $row['ObjectID'];
	$objName = $row['Name'];
	$question = $row['Question'];
	$objType = $row['Type'];
	
	// Trim question to a reasonable length
	$question = strlen($question) > $maxLen ? substr($question, 0, $maxLen).'...' : $question;
	
	// If it's a different member or calling from the item before it,
	// show a new divider so the rendering is organized by names/callings.
	if ($objID != $lastObjID && $objName != $lastObjName):
		if ($lastObjID != 0) echo '</div>';
	?>
						<br>
						<table>
							<tr>
								<th>
									<a href="javascript:" title="Expand/collapse" class="toggler" id="toggler-<?php echo $objID; ?>-<?php echo $objType; ?>">
										<span class="arrow"><i class="icon-expand-alt"></i></span> <?php echo $objName ?></a>
									</a>
								</th>
								<td><?php echo $objType ?></td>
							</tr>
						</table>
						<div class="pers" id="pers-<?php echo $objID; ?>-<?php echo $objType; ?>">
	<?php endif; ?>
						<span class="permission">
							<a href="api/deletepermission?id=<?php echo($perID); ?>" title="Revoke permission" class="delPer del"><i class="icon-remove"></i></a>
							<?php echo $question ?>
						</span>
	
<?php
	$lastObjID = $objID;
	$lastObjName = $objName;
endwhile;
?>			
					</div>
				</div>

				<a href="#"><i class="icon-arrow-up"></i> Top</a>

			</div>

		</div>

		<?php include "../includes/footer.php"; ?>
		<?php include "../includes/nav.php"; ?>
<script>

$(function() {
	
	// List of permissions (expand / collapse sections)
	$('.toggler').click(function() {
		var selector = $(this).attr('id').substr(8);
		$('div#pers-' + selector).slideToggle('fast');
		var arrow = $('.arrow', this).html();
		$('.arrow', this).html(arrow == '<i class="icon-collapse-alt"></i>' ? '<i class="icon-expand-alt"></i>' : '<i class="icon-collapse-alt"></i>');		
	});

	// Select all checkbox for list of questions
	$('#selectAll').change(function() {
		$('input[type=checkbox]').prop('checked', $(this).prop('checked'));
	});

	// Choosing a member, not a calling
	$('#chooseMember').change(function() {
		$('#selectCalling select').val('');
		$('#selectCalling').hide();
		$('#selectMember').show();
	});

	// Choosing a calling, not a member
	$('#chooseCalling').change(function() {
		$('#selectMember select').val('');
		$('#selectMember').hide();
		$('#selectCalling').show();
	});

	// Delete a permission link clicked
	$('.delPer').hijax({
		complete: function(xhr)
		{
			if (xhr.status == 200)
			{
				$.sticky("Deleted");
				$(this).closest('.permission').hide('fast', function() {
					$(this).remove();
				});
			}
			else
				$.sticky(xhr.responseText || "There was a problem. Please try again?", { classList: 'error' });
		}
	});
});


function toggle(source) {
	checkboxes = document.getElementsByName('questionID[]');
	for(var i in checkboxes)
		checkboxes[i].checked = source.checked;
}
</script>
	
	</body>
</html>