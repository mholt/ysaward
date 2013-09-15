<?php
require_once "../lib/init.php";
protectPage(11);

// Get a list of all current members in the ward
$q = "SELECT ID FROM Members WHERE WardID=$MEMBER->WardID ORDER BY FirstName ASC, LastName ASC";
$r = DB::Run($q);

// Get a list of all the callings in the ward
$q2 = "SELECT ID FROM Callings WHERE WardID=$MEMBER->WardID ORDER BY Name ASC";
$r2 = DB::Run($q2);


// Build that list into a dropdown
$maxLen = 45; // Max length of the calling name for display
$dropdown = "<select size=\"1\" name=\"newCallings[%s]\">\r\n<option value=\"\" selected>Give calling...</option>\r\n";
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
<!DOCTYPE html>
<html>
	<head>
		<title>Manage callings &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
		<?php include "../includes/head.php"; ?>
		<style>
		table {
			width: 100%;
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

		.unassign {
			visibility: hidden;
		}

		tr:nth-child(even) {
			background: #EFEFEF;
		}

		tr:nth-child(odd) {
			background: #FFF;
		}

		tr:hover .unassign {
			visibility: visible;
		}

		tr:hover .add {
			display: inline;
		}

		td.member-name {
			font-weight: bold;
			width: 30%;
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

		<h1>Manage callings</h1>

		<div class="grid-container">

			<div class="grid-100">

				<div class="instructions">
					<p>
						Update this page whenever new callings or releasings are announced. Use callings together with
						<a href="permissions">permissions</a> to help members get info they need.
						This page is entirely independent of MLS, which needs to be updated separately.
					</p>
					<b>1.</b> On the left side, create callings by giving them a name. &nbsp; &nbsp; &nbsp;
					<b>2.</b> On the right side, assign members to their callings.
				</div>

			</div>

			<div class="grid-40">
				
				<h2>Add/delete callings</h2>
				
				<table>
					<tr id="add-calling-tr">
						<td colspan="2">
							<form method="post" action="api/addcalling" id="add-calling">
								<input type="text" name="name" placeholder="New calling name" maxlength="120">
								<br>
								<div class="text-right">
									<input type="submit" value="Save">
								</div>
							</form><br>
						</td>
					</tr>
					<?php
						while ($row = mysql_fetch_array($r2)):
							$c = Calling::Load($row['ID']);
					?>
					<tr>
						<td class="del-td">
							<?php if (!$c->Preset()): ?>
							<a href="api/deletecalling?id=<?php echo($c->ID()); ?>" class="delcalling del" data-id="<?php echo $c->ID(); ?>"><i class="icon-remove-sign"></i></a>
							<?php endif; ?>
						</td>
						<td><?php echo $c->Name; ?></td>
					</tr>
					<?php endwhile; ?>
				</table>

				<br>
				<a href="#">Top <i class="icon-arrow-up"></i></a>
				<br><br>
			</div>


			<div class="grid-60">
				
				<h2>Assign callings to members</h2>
				
				<table>
				<?php
					while ($row = mysql_fetch_array($r)):
						$m = Member::Load($row['ID']);
				?>
					<tr id="mem-<?php echo $m->ID(); ?>">
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
								<a href="api/unassigncalling?cID=<?php echo $c->ID(); ?>&mID=<?php echo $m->ID(); ?>" class="unassign del"><i class="icon-remove"></i></a>
								<br>
							</div>
							<?php
										}
									}
									// Next, show a dropdown so the user can assign a calling
									echo "<span class='add'>";
									printf($dropdown, $m->ID());
									echo "</span>";
							?>
						</td>
					</tr>
					<?php endwhile; ?>
				</table>

				<br>
				<a href="#"><i class="icon-arrow-up"></i> Top</a>

			</div>

		</div>

		<?php include "../includes/footer.php"; ?>
		<?php include "../includes/nav.php"; ?>

<script>
$(function()
{

	// FORM SUBMIT: Add calling
	$('#add-calling').hijax({
		before: function(){
			if ($('input[name=name]').val().length < 2)
			{
				$.sticky("Please type a calling name", { classList: 'error' });
				return false;
			}
		},
		complete: function(xhr) {
			if (xhr.status == 200)
			{
				// The response text contains the calling ID
				$.sticky("Created calling successfully");
				$('#add-calling-tr').after('<tr><td class="del-td"><a href="api/deletecalling?id='+xhr.responseText+'" class="delcalling del" data-id="'+xhr.responseText+'"><i class="icon-remove-sign"></i></a></td>' +
					'<td>'+$('input[name=name]').val()+'</td></tr>');
				$('.calling-list select').prepend('<option value="'+xhr.responseText+'">'+$('input[name=name]').val()+'</option>');
				$('input[name=name]').val('').focus();
			}
			else
				$.sticky(xhr.responseText || "There was a problem and the calling could not be created.", { classList: 'error' });
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
				$.sticky("Calling deleted.");
				this.parents('tr').first().fadeOut('medium');
				$('.call-'+callID).fadeOut('medium');
				$('option[value='+callID+']').remove();
			}
			else
				$.sticky(xhr.responseText || "There was a problem and the calling could not be deleted.", { classList: 'error' });
		}
	});

	// SELECT CHANGED: Assign a calling
	$('body').on('change', '.calling-list select', function(e) {
		var select = $(this);
		var memID = $(this).parents('tr').first().attr('id').substr(4);
		var callID = $(this).val();
		var callName = $('option:selected', select).text();

		$.hijax({
			url: 'api/assigncalling?mID='+memID+'&cID='+callID,
			complete: function (xhr) {
				if (xhr.status == 200)
				{
					$.sticky("Saved");
					select
						.parents('.add')
						.first()
						.before('<div class="call-'+callID+'">'+callName+' &nbsp; '
							+ '<a href="api/unassigncalling?cID='+callID+'&mID='+memID+'" class="unassign del"><i class="icon-remove"></i></a>'
							+ '<br></div>');
				}
				else
					$.sticky(xhr.responseText || "There was a problem and the member could not be assigned to this calling.", { classList: 'error' });

				select.val('');
			}
		});
	});


	// LINK CLICKED: Unassign member from a calling (remove calling from member)
	$('.unassign').hijax({
		complete: function(xhr) {
			if (xhr.status == 200)
			{
				$.sticky("Saved");
				this.parents('div').first().hide('medium');
			}
			else
				$.sticky(xhr.responseText || "There was a problem and the member could not be unassigned from the calling.", { classList: 'error' });
		}
	});


});
</script>
	</body>
</html>