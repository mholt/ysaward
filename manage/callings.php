<?php
require_once("../lib/init.php");
protectPage(11);

// Get a list of all current members in the ward
$q = "SELECT ID FROM Members WHERE WardID=$MEMBER->WardID ORDER BY FirstName ASC, LastName ASC";
$r = DB::Run($q);

// Get a list of all the callings in the ward
$q2 = "SELECT ID FROM Callings WHERE WardID=$MEMBER->WardID ORDER BY Name ASC";
$r2 = DB::Run($q2);


// Build that list into a dropdown
$maxLen = 45; // Max length of the calling name for display
$dropdown = "<select size=\"1\" name=\"newCallings[%s]\">\r\n<option value=\"\" selected=\"selected\"></option>\r\n";
while ($row2 = mysql_fetch_array($r2))
{
	$calling = Calling::Load($row2['ID']);
	$name = strlen($calling->Name) > $maxLen ? substr($calling->Name, 0, $maxLen).'...' : $calling->Name;
	$dropdown .= "<option value=\"".$calling->ID()."\">".strip_tags($name)."</option>\r\n";
}
$dropdown .= "</select>";

 // Set internal result pointer back to row 0
mysql_data_seek($r2, 0);
?>
<html>
<head>
	<title>Callings &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
	<?php include("../includes/head.php"); ?>
<style>
table {
	width: 85%;
	max-width: 500px;
	margin: 0px auto;
}

td {
	padding: 7px;
}

.del-td {
	font-size: 12px;
	width: 50px;
}

.add {
	display: none;
}

tr:hover .add {
	display: inline;
}

td.member-name {
	width: 200px;
	vertical-align: top;
	font-weight: bold;
}

td.calling-list {
	height: 3em;
}
</style>
</head>
<body>
	
	<?php include("../includes/header.php"); ?>
	
	<article class="grid-12 group">
		
		<h1>Manage callings</h1>

		<div class="instructions">
			<p>
				Update this page whenever new callings or releasings are announced. Use callings together with
				<a href="permissions.php">permissions</a> to help members get info they need.
				This page is entirely independent of MLS, which needs to be updated separately.
			</p>
			<b>1.</b> On the left side, create callings by giving them a name. &nbsp; &nbsp; &nbsp;
			<b>2.</b> On the right side, assign members to their callings.
		</div>


		<section class="g-5">
			
			<h2>Add/delete callings</h2>
			
			<table>
				<tr id="add-calling-tr">
					<td colspan="2" style="text-align: center; border-bottom: 1px solid #888;">
						<form method="post" action="api/addcalling.php" id="add-calling">
							<b>Add calling:</b> <input type="text" name="name" size="15" maxlength="120">
							<input type="submit" value="Save" class="button sm">
						</form><br>
					</td>
				</tr>
<?php
	$i = 0;
	while ($row = mysql_fetch_array($r2))
	{
		$c = Calling::Load($row['ID']);
?>
				<tr style="background: <?php echo $i % 2 == 0 ? "#FFF" : "#F0F0F0"; ?>;">
					<td class="del-td">
						<?php if (!$c->Preset()): ?>
						<a href="api/deletecalling.php?id=<?php echo($c->ID()); ?>" class="delcalling" data-id="<?php echo $c->ID(); ?>">Delete</a>
						<?php endif; ?>
					</td>
					<td><?php echo $c->Name; ?></td>
				</tr>
<?php
	$i ++;
	}
?>
			</table>

			<br><br>
			<a href="#">Back to top</a>

		</section>


		<section class="g-7">
			
			<h2>Assign members callings</h2>
			
			<table style="width: 100%;">
<?php
	$i = 0;
	while ($row = mysql_fetch_array($r))
	{
		$m = Member::Load($row['ID']);
?>
				<tr style="background: <?php echo $i % 2 == 0 ? "#FFF" : "#F0F0F0"; ?>;" id="mem-<?php echo $m->ID(); ?>">
					<td class="member-name"><?php echo $m->FirstName.' '.$m->LastName; ?></td>
					<td class="calling-list">
<?php
		// List this member's callings, with the option to delete the association
		$callings = $m->Callings();
		if (count($callings) > 0)
		{
			foreach ($m->Callings() as $c)
			{
?>
						<div class="call-<?php echo $c->ID(); ?>"><?php echo $c->Name; ?>
							&nbsp;
							<span style="font-size: 12px;">
								[<a href="api/unassigncalling.php?cID=<?php echo $c->ID(); ?>&mID=<?php echo $m->ID(); ?>" class="unassign">Remove</a>]
							</span>
							<br>
						</div>
<?php
			}
		}
		// Next, show a dropdown so the user can assign a calling
		echo "<span class='add'>Add: ";
		printf($dropdown, $m->ID());
		echo "</span>";
?>
					</td>
				</tr>
<?php
	$i ++;
	}
?>
			</table>
			<br><br>
			<a href="#">Back to top</a>
			<br><br>

		</section>

	</article>
<script type="text/javascript">
$(function() {

	// FORM SUBMIT: Add calling
	$('#add-calling').hijax({
		before: function(){
			if ($('input[name=name]').val().length < 2)
			{
				toastr.error("Please type a calling name");
				return false;
			}
		},
		complete: function(xhr) {
			if (xhr.status == 200)
			{
				// The response text contains the calling ID
				toastr.success("Created calling successfully");
				$('#add-calling-tr').after('<tr><td class="del-td"><a href="api/deletecalling.php?id='+xhr.responseText+'" class="delcalling" data-id="'+xhr.responseText+'">Delete</a></td>' +
					'<td>'+$('input[name=name]').val()+'</td></tr>');
				$('.calling-list select').prepend('<option value="'+xhr.responseText+'">'+$('input[name=name]').val()+'</option>');
				$('input[name=name]').val('').focus();
			}
			else
				toastr.error(xhr.responseText || "There was a problem and the calling could not be created.");
		}
	});


	// LINK CLICKED: Delete a specific calling
	$('.delcalling').hijax({
		before: function(xhr) {
			return confirm("Are you sure?");
		},
		complete: function(xhr) {
			if (xhr.status == 200)
			{
				var callID = this.data("id");
				toastr.success("Calling deleted.");
				this.parents('tr').first().fadeOut('medium');
				$('.call-'+callID).fadeOut('medium');
				$('option[value='+callID+']').remove();
			}
			else
				toastr.error(xhr.responseText || "There was a problem and the calling could not be deleted.");
		}
	});

	// SELECT CHANGED: Assign a calling
	$('body').on('change', '.calling-list select', function(e) {
		var select = $(this);
		var memID = $(this).parents('tr').first().attr('id').substr(4);
		var callID = $(this).val();
		var callName = $('option:selected', select).text();

		$.hijax({
			url: 'api/assigncalling.php?mID='+memID+'&cID='+callID,
			complete: function (xhr) {
				if (xhr.status == 200)
				{
					toastr.success("Saved");
					select
						.parents('.add')
						.first()
						.before('<div class="call-'+callID+'">'+callName+' &nbsp; '
							+ '<span style="font-size: 12px;">[<a href="api/unassigncalling.php?cID='+callID+'&mID='+memID+'" class="unassign">Remove</a>]</span>'
							+ '<br></div>');
				}
				else
					toastr.error(xhr.responseText || "There was a problem and the member could not be assigned to this calling.");

				select.val('');
			}
		});
	});


	// LINK CLICKED: Unassign member from a calling (remove calling from member)
	$('.unassign').hijax({
		complete: function(xhr) {
			if (xhr.status == 200)
			{
				toastr.success("Saved");
				this.parents('div').first().hide('medium');
			}
			else
				toastr.error(xhr.responseText || "There was a problem and the member could not be unassigned from the calling.");
		}
	});


});
</script>
<?php include("../includes/footer.php"); ?>
