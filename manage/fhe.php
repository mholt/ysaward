<?php
require_once "../lib/init.php";
protectPage(7);

// Get a list of all members of the ward by name
$mems = array();
$q = "SELECT ID FROM Members WHERE WardID=$MEMBER->WardID ORDER BY FirstName, LastName ASC";
$r = DB::Run($q);
while ($row = mysql_fetch_array($r))
	array_push($mems, Member::Load($row['ID']));

// Build list of options
$memList = "";
foreach ($mems as $mem)
	$memList .= "\r\n<option value=\"{$mem->ID()}\">".$mem->FirstName()." ".$mem->LastName."</option>";
$memList .= "\r\n";

// Get a list of FHE groups
$groups = array();
$q2 = "SELECT ID FROM FheGroups WHERE WardID=$MEMBER->WardID ORDER BY GroupName ASC";
$r2 = DB::Run($q2);
while ($row = mysql_fetch_array($r2))
	array_push($groups, FheGroup::Load($row['ID']));

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Manage FHE groups &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
		<?php include "../includes/head.php"; ?>
		<style>
		.no-group {
			background-color: #FFFFAA !important;
		}
		
		.no-leader {
			padding: 10px;
			background-color: #FFFFAA;
		}

		td {
			vertical-align: middle;
		}

		tr:nth-child(even) {
			background: #FFF;
		}

		tr:nth-child(odd) {
			background: #EFEFEF;
		}

		td select {
			margin: 5px 0;
			font-size: 14px;
		}
		</style>
	</head>
	<body>
		<?php include "../includes/header.php"; ?>

		<h1>Manage FHE Groups</h1>

		<div class="grid-container">

			<div class="grid-100">
				<div class="instructions">
					<p>
						Make sure members are assigned to their proper FHE group. Most
						groups are titled simply by their number, such as "1" or "3" &mdash; unless
						your ward decides to name the groups.
					</p>

					<p>
						Each group usually has two leaders, but up to three may be assigned. When you assign
						a member to be a group leader, they will automatically join that group.
					</p>
					
					<b>1.</b> On the right, create the groups by giving them a name and assigning leaders. &nbsp; &nbsp;
					<br>
					<b>2.</b> On the left, assign each member to a group.
					
				</div>
			</div>

			<div class="grid-40">

				<div class="padded card wide">

					<h2>Put members in FHE groups</h2>


					<div id="nogroup" class="hide" style="background: #FFFFAA; padding: 10px; color: #CC0000; width: 90%;">
						At least one member is not assigned to any group.
					</div><br>

					<table style="width: 100%;">
						<?php $i = 0; foreach ($mems as $mem): ?>

						<?php if ($mem->FheGroup > 0): ?>
						<tr>
						<?php else: ?>
						<tr class="no-group">
						<?php endif; ?>
							<td style="padding: 3px;">
								<?php echo $mem->FirstName()." ".$mem->LastName; ?> &nbsp;
							</td>
							<td style="padding: 3px;">
								<form method="POST" action="api/fhe.php?assign" class="assign">
									<input type="hidden" name="user" value="<?php echo $mem->ID(); ?>">
									<?php
										echo '<select size="1" class="groups" name="group"><option value=""></option>';
										foreach ($groups as $group)
										{
											echo "\r\n" . "<option value=\"{$group->ID()}\"";
											if ($mem->FheGroup == $group->ID())
												echo ' selected="selected"';
											echo ">{$group->GroupName}</option>";
										}
										echo "\r\n</select>";
									?>
								</form>
							</td>
						</tr>
						<?php endforeach; ?>
					</table>

					<br><a href="#">Top <i class="fa fa-arrow-up"></i></a>
				</div>
			</div>


			<div class="grid-60">
				<div class="padded card wide">
					<h2>Edit groups and leaders</h2>

					<div id="noleader" class="hide" style="background: #FFFFAA; padding: 10px; color: #CC0000;">
						At least one of the groups does not have any leaders. Please be sure to assign one.
					</div><br>

					<form method="POST" action="api/fhe?new" id="newGroup" style="background-color: #F0F0F0; padding: 10px;">
						<h3>Create new group</h3>
						<!--<i><small>
							Each group can have up to three leaders, but usually only two are assigned.
							<br>Leaders will automatically be moved to their assigned groups.
						</small></i>-->
						<input type="text" size="20" name="groupname" placeholder="Group name (example: 1)">
						<br>
						<select size="1" name="ldr1"><option value="">(Leader 1)</option><?php echo $memList; ?></select>
						<select size="1" name="ldr2"><option value="">(Leader 2)</option><?php echo $memList; ?></select>
						<select size="1" name="ldr3"><option value="">(Leader 3) (optional)</option><?php echo $memList; ?></select>
						<input type="submit" value="Create">
					</form>
					<br><br><br>


					<h3>Existing groups</h3>
					<!--<i><small>New group leaders will automatically join their assigned group.</small></i>-->

					<?php foreach ($groups as $group): ?>

					<?php $hasLeader = $group->Leader1 || $group->Leader2 || $group->Leader3; ?>

					<form method="POST" action="api/fhe?edit" class="edit<?php if (!$hasLeader) echo ' no-leader'; ?>">
						<input type="hidden" name="id" value="<?php echo $group->ID(); ?>">
						<input type="text" size="20" name="groupname" value="<?php echo $group->GroupName; ?>" placeholder="Group name (example: 1)">
						<small>
							<a class="del" href="api/fhe?del&id=<?php echo $group->ID(); ?>"><i class="fa fa-times"></i> Delete</a>
						</small>
						<br><br>
						<select size="1" name="ldr1"><option value="">(Leader 1)</option>
						<?php
							foreach ($mems as $mem)
							{
								echo "\r\n" . "<option value=\"{$mem->ID()}\"";
								if ($group->Leader1 == $mem->ID())
									echo ' selected="selected"';
								echo ">{$mem->FirstName()} {$mem->LastName}</option>";
							}
						?>
						</select>
						<select size="1" name="ldr2"><option value="">(Leader 2)</option>
						<?php
							foreach ($mems as $mem)
							{
								echo "\r\n" . "<option value=\"{$mem->ID()}\"";
								if ($group->Leader2 == $mem->ID())
									echo ' selected="selected"';
								echo ">{$mem->FirstName()} {$mem->LastName}</option>";
							}
						?>
						</select>
						<select size="1" name="ldr3"><option value="">(Leader 3) (optional)</option>
						<?php
							foreach ($mems as $mem)
							{
								echo "\r\n" . "<option value=\"{$mem->ID()}\"";
								if ($group->Leader3 == $mem->ID())
									echo ' selected="selected"';
								echo ">{$mem->FirstName()} {$mem->LastName}</option>";
							}
						?>
						</select>

							<input type="submit" value="Save">
						</form>
						<br><hr class="line">
						<br>
					<?php endforeach; ?>

					<?php if (count($groups) == 0): ?><p>No FHE groups! Make some?</p><?php endif; ?>

					<a href="#"><i class="fa fa-arrow-up"></i> Top</a>
				</div>
			</div>


		</div>


		<?php include "../includes/footer.php"; ?>
		<?php include "../includes/nav.php"; ?>

<script>
$(function()
{
	var reloading = false;

	// Update group assignment form submit
	$('form.assign').hijax({
		before: function() {
			return !reloading;
		},
		complete: function(xhr) {
			if (xhr.status == 200)
			{
				$.sticky(xhr.responseText);
				$('select', 'form.assign').filter(function() {
					return $(this).val() > 0;
				}).closest('tr').removeClass('no-group');
				$('select', 'form.assign').filter(function() {
					return $(this).val() == 0;
				}).closest('tr').addClass('no-group');

				groupHlt();
			}
			else
				$.sticky(xhr.responseText, { classList: 'error' });

			// We have to reload the groups to get updated leader info
			if (xhr.responseText.indexOf('no longer a leader') > -1)
			{
				$.sticky("Reloading; please wait...");
				reloading = true;
				setTimeout(function() { window.location.reload(); }, 1000);
			}
		}
	});

	// Update group assignment: just submit the form (see above)
	$('.groups').change(function() {
		$(this).closest('form.assign').submit();
	});

	// Create new group form submit
	$('#newGroup').hijax({
		before: function() {
			return !reloading;
		},
		complete: function(xhr) {
			if (xhr.status == 200)
			{
				$.sticky("FHE group created successfully!<br>Reloading; please wait...");
				reloading = true;
				setTimeout(function() { window.location.reload(); }, 1000);
			}
			else
				$.sticky(xhr.responseText, { classList: 'error' });
		}
	});

	// Delete a group
	$('a.del').hijax({
		before: function() {
			if (!reloading)
				return confirm("Are you sure you want to delete this FHE group? All its members will be removed from any group until re-assigned.");
			else
				return false;
		},
		complete: function(xhr) {
			if (xhr.status == 200)
			{
				$.sticky("FHE group deleted.<br>Reloading; please wait...");
				reloading = true;
				setTimeout(function() { window.location.reload(); }, 1000);
			}
			else
				$.sticky(xhr.responseText, { classList: 'error' });
		}
	});

	// Edit a group
	$('form.edit').hijax({
		before: function() {
			return !reloading;
		},
		complete: function(xhr) {
			if (xhr.status == 200)
			{
				$.sticky("Changes saved.<br>Reloading; please wait...");
				reloading = true;
				setTimeout(function() { window.location.reload(); }, 1000);

				/*
				Commented out because this was a bit complicated / unreliable... TODO though. Then we could eliminate the reload.

				// Add highlights?
				$('form.edit').filter(function() {
					return $('select', this).filter(function() { return !$(this).val(); }).length == 3;
				}).addClass('no-leader');

				// Remove highlights?
				$('form.edit').filter(function() {
					return $('select', this).filter(function() { console.log($(this).val()); return !$(this).val(); }).length < 3;
				}).removeClass('no-leader');

				leaderHlt();
				*/
			}
			else
				$.sticky(xhr.responseText, { classList: 'error' });
		}
	});

	leaderHlt();
	groupHlt();

});

function leaderHlt()
{
	// All groups have a leader?
	if ($('.no-leader').length > 0)
		$('#noleader').show();
	else
		$('#noleader').hide();
}


function groupHlt()
{
	// All members have a group?
	if ($('.no-group').length > 0)
		$('#nogroup').show();
	else
		$('#nogroup').hide();
}
</script>

	</body>
</html>