<?php
require_once("../lib/init.php");
protectPage(7);

// Get a list of all members of the ward by name
$mems = array();
$q = "SELECT ID FROM Members WHERE WardID=$MEMBER->WardID ORDER BY FirstName, LastName ASC";
$r = DB::Run($q);
while ($row = mysql_fetch_array($r))
	array_push($mems, Member::Load($row['ID']));

// Build list of options
$memList = "<option value=''></option>";
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
<html>
<head>
	<title>Manage FHE Groups &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
	<?php include("../includes/head.php"); ?>
	<style>
	.no-group { background-color: #FFFFAA !important; }
	.no-leader { padding: 10px; background-color: #FFFFAA; }
	h3 { margin: 0px; }
	</style>
</head>
<body>
	
	<?php include("../includes/header.php"); ?>
	
	<article class="grid-12 group">
		
		<h1>Manage FHE Groups</h1>

		<section class="g-12">
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
				<b>2.</b> On the left, assign each member to a group.
			</div><br>
		</section>
		<hr class="clear">

		<section class="g-5 text-center">
			<h2>Assign members to FHE groups</h2>


			<div id="nogroup" class="hide" style="background: #FFFFAA; padding: 10px; color: #CC0000; width: 90%;">
				At least one member is not assigned to any group.
			</div><br>

			<table style="border-collapse: collapse; min-width: 300px;" class="center">
				<?php $i = 0; foreach ($mems as $mem): $i++; ?>

				<?php if ($mem->FheGroup > 0): ?>
				<tr style="background-color: <?php echo $i % 2 == 0 ? '#EFEFEF' : '#FFF'; ?>">
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

			<br><br><a href="#" class="toTop">Back to top</a><br><br>
		</section>


		<section class="g-7">
			<h2>Edit groups and leaders</h2>

			<div id="noleader" class="hide" style="background: #FFFFAA; padding: 10px; color: #CC0000;">
				At least one of the groups does not have any leaders. Please be sure to assign one.
			</div><br>

			<form method="POST" action="api/fhe.php?new" id="newGroup" style="background-color: #F0F0F0; padding: 10px;">
				<h3>Create new group</h3>
				<!--<i><small>
					Each group can have up to three leaders, but usually only two are assigned.
					<br>Leaders will automatically be moved to their assigned groups.
				</small></i>-->
				<br>
				Group name: <input type="text" size="20" name="groupname"> <i>(example: 1)</i>
				<br><br>
				Leader 1: <select size="1" name="ldr1"><?php echo $memList; ?></select>
				<br>
				Leader 2: <select size="1" name="ldr2"><?php echo $memList; ?></select>
				<br>
				Leader 3: <select size="1" name="ldr3"><?php echo $memList; ?></select> <i>(not usually needed)</i>
				<br><br>
				<div style="text-indent: 120px;">
					<input type="submit" class="button" value="&#10003; Create">
				</div>
			</form>
			<br><br><br>


			<h3>Existing groups</h3>
			<!--<i><small>New group leaders will automatically join their assigned group.</small></i>-->

			<?php foreach ($groups as $group): ?>

			<?php $hasLeader = $group->Leader1 || $group->Leader2 || $group->Leader3; ?>

			<form method="POST" action="api/fhe.php?edit" class="edit<?php if (!$hasLeader) echo ' no-leader'; ?>">
				<input type="hidden" name="id" value="<?php echo $group->ID(); ?>">
				<br>
				Group name: <input type="text" size="20" name="groupname" value="<?php echo $group->GroupName; ?>">
				<br>
				Leader 1:
				<select size="1" name="ldr1"><option value=""></option>
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
				<br>
				Leader 2:
				<select size="1" name="ldr2"><option value=""></option>
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
				<br>
				Leader 3:
				<select size="1" name="ldr3"><option value=""></option>
				<?php
					foreach ($mems as $mem)
					{
						echo "\r\n" . "<option value=\"{$mem->ID()}\"";
						if ($group->Leader3 == $mem->ID())
							echo ' selected="selected"';
						echo ">{$mem->FirstName()} {$mem->LastName}</option>";
					}
				?>
				</select> <i><small>(not usually needed)</small></i>

				<br><br>
				<div style="text-indent: 120px;">
					<input type="submit" value="&#10003; Save" class="button sm">
					&nbsp;  &nbsp;
					<small><a class="del" href="api/fhe.php?del&id=<?php echo $group->ID(); ?>" style="color: #CC0000;">Delete</a></small>
				</div>
				</form>
				<br><hr>
			<?php endforeach; ?>

			<?php if (count($groups) == 0): ?><p>No FHE groups! Make some?</p><?php endif; ?>

			<br><br><a href="#" class="toTop">Back to top</a><br><br>
		</section>
		<hr class="clear">
		
	</article>


<script type="text/javascript">
$(function() {
	var reloading = false;

	// Update group assignment form submit
	$('form.assign').hijax({
		before: function() {
			return !reloading;
		},
		complete: function(xhr) {
			if (xhr.status == 200)
			{
				toastr.success(xhr.responseText);
				$('select', 'form.assign').filter(function() {
					return $(this).val() > 0;
				}).closest('tr').removeClass('no-group');
				$('select', 'form.assign').filter(function() {
					return $(this).val() == 0;
				}).closest('tr').addClass('no-group');

				groupHlt();
			}
			else
				toastr.error(xhr.responseText);

			// We have to reload the groups to get updated leader info
			if (xhr.responseText.indexOf('no longer a leader') > -1)
			{
				toastr.warning("Reloading; please wait...");
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
				toastr.success("FHE group created successfully!<br>Reloading; please wait...");
				reloading = true;
				setTimeout(function() { window.location.reload(); }, 1000);
			}
			else
				toastr.error(xhr.responseText);
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
				toastr.success("FHE group deleted.<br>Reloading; please wait...");
				reloading = true;
				setTimeout(function() { window.location.reload(); }, 1000);
			}
			else
				toastr.error(xhr.responseText);
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
				toastr.success("Changes saved.<br>Reloading; please wait...");
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
				toastr.error(xhr.responseText);
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
	
<?php include("../includes/footer.php"); ?>