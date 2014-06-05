<?php
require_once "../lib/init.php";
protectPage(10);

$r1 = DB::Run("SELECT ID FROM Privileges ORDER BY Privilege ASC");
$r2 = DB::Run("SELECT ID FROM Members WHERE WardID='$MEMBER->WardID' ORDER BY FirstName ASC, LastName ASC");
$r3 = DB::Run("SELECT ID FROM Callings WHERE WardID='$MEMBER->WardID' ORDER BY Name ASC");

$maxLen = 65; // Maximum display length of a calling name


?>
<!DOCTYPE html>
<html>
	<head>
		<title>Manage site privileges &mdash; <?php echo $WARD ? $WARD->Name." Ward" : SITE_NAME; ?></title>
		<?php include "../includes/head.php"; ?>
		<style>
		.privlist {
			width: 100%;
		}

		form, label {
			font-size: 12px;
			line-height: 1.75em;
		}

		.privList td, .privList th {
			padding: 5px;
			border: 1px solid #D0D0D0;
			font-size: 12px;
		}

		.privlist th {
			background: #D0D0D0;
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

		<h1>Site privileges</h1>

		<div class="grid-container">

			<div class="grid-100">

				<div class="instructions">
					<p>
						Use this page to grant site privileges to certain members or callings. These are
						different from permissions which only deal with the survey. This page pertains
						to things of the website (for your own ward). Grant privileges as needed, but
						be careful to consider privacy implications as more and more privileges are
						granted.
					</p>

					<b>On the left,</b> grant privileges to individual members. &nbsp; &nbsp; &nbsp; <b>On the right,</b>
					grant privileges to all members with certain callings.
					
					<div class="text-center"><b>Below (on both sides)</b>, you can revoke any granted privileges.</div>
				</div>

			</div>


			<div class="grid-50">
				<div class="wide padded card">
				
					<h2 id="to-member">For MEMBERS</h2>

					<form method="POST" action="api/doprivileges">
						<input type="hidden" name="action" value="grant">
						<input type="hidden" name="objType" value="Member">


						<b>Member:</b>
						<br>
						<select size="1" name="memberID">
							<option value="" selected="selected"></option>
						<?php
							while ($row = mysql_fetch_array($r2))
							{
								$m = Member::Load($row['ID']);
						?>
							<option value="<?php echo $m->ID(); ?>"><?php echo strip_tags($m->FirstName.' '.$m->LastName); ?></option>
						<?php
							}
						?>
						</select><br>



						<b>Privilege:</b><br>
						<?php
						while ($row = mysql_fetch_array($r1))
						{
							$priv = Privilege::Load($row['ID']);
						?>
						<label title="<?php echo($priv->HelpText()); ?>"><input type="checkbox" name="priv[]" value="<?php echo $priv->ID(); ?>" class="standard"> <?php echo $priv->Privilege(); ?></label><br>
						<?php }	?>
						<br>
						<input type="submit" value="Grant to Member" class="button sm">

					</form>
					<br>

					<h2 id="by-member">Privileges granted to members</h2>

					<table class="privList">
						<tr>
							<th>Member</th>
							<th>Privilege</th>
							<th>Options</th>
						</tr>
					<?php
					$rm = DB::Run("SELECT MemberID,PrivilegeID FROM GrantedPrivileges INNER JOIN Members ON Members.ID = MemberID INNER JOIN Privileges ON Privileges.ID = GrantedPrivileges.PrivilegeID WHERE MemberID > 0 AND Members.WardID={$MEMBER->WardID} ORDER BY Members.FirstName ASC, Members.LastName ASC");
					while ($row = mysql_fetch_array($rm)):
						$priv = Privilege::Load($row['PrivilegeID']);
						$mem = Member::Load($row['MemberID']);
					?>
						<tr>
							<td>
								<b><?php echo $mem->FirstName.' '.$mem->LastName; ?></b>
							</td>
							<td>
								<span title="<?php echo $priv->HelpText(); ?>"><?php echo $priv->Privilege(); ?></span>
							</td>
							<td>
								<a href="api/doprivileges?action=revoke&id=<?php echo $priv->ID(); ?>&m=<?php echo $mem->ID(); ?>" title="Remove this privilege">Revoke</a>
							</td>
						</tr>
					<?php endwhile; ?>
					</table>
				</div>

				<a href="#">Top <i class="fa fa-arrow-up"></i></a>
			</div>





			<div class="grid-50">
				<div class="wide padded card">

					<h2 id="to-calling">For CALLINGS</h2>

					<form method="POST" action="api/doprivileges">
						<input type="hidden" name="action" value="grant">
						<input type="hidden" name="objType" value="Calling">

						<b>Calling:</b>
						<br>
						<select size="1" name="callingID">
							<option value="" selected="selected"></option>
						<?php
							while ($row = mysql_fetch_array($r3))
							{
								$c = Calling::Load($row['ID']);
								$name = strlen($c->Name) > $maxLen ? substr($c->Name, 0, $maxLen).'...' : $c->Name;
						?>
							<option value="<?php echo $c->ID(); ?>"><?php echo strip_tags($name); ?></option>
						<?php
							}
						?>
						</select>
						<br>

						<b>Privilege:</b><br>
						<?php

						// Go back to beginning of priv. list
						if (mysql_num_rows($r1) > 0)
							mysql_data_seek($r1, 0);

						while ($row = mysql_fetch_array($r1))
						{
							$priv = Privilege::Load($row['ID']);
						?>
						<label title="<?php echo($priv->HelpText()); ?>"><input type="checkbox" name="priv[]" value="<?php echo $priv->ID(); ?>" class="standard"> <?php echo $priv->Privilege(); ?></label><br>
						<?php }	?>
						<br>
						<input type="submit" value="Grant to Calling" class="button sm">

					</form>
					<br>

					<h2 id="by-calling">Privileges granted to callings</h2>

					<table class="privList">
						<tr>
							<th>Calling</th>
							<th>Privilege</th>
							<th>Options</th>
						</tr>
					<?php
					$rm = DB::Run("SELECT CallingID, PrivilegeID FROM GrantedPrivileges INNER JOIN Callings ON Callings.ID = CallingID INNER JOIN Privileges ON Privileges.ID = GrantedPrivileges.PrivilegeID WHERE CallingID > 0 AND Callings.WardID={$MEMBER->WardID} ORDER BY Callings.Name ASC, Privileges.Privilege ASC");
					while ($row = mysql_fetch_array($rm)):
						$priv = Privilege::Load($row['PrivilegeID']);
						$call = Calling::Load($row['CallingID']);
					?>
						<tr>
							<td>
								<b><?php echo $call->Name; ?></b>
							</td>
							<td>
								<span title="<?php echo $priv->HelpText(); ?>"><?php echo $priv->Privilege(); ?></span>
							</td>
							<td>
								<a href="api/doprivileges?action=revoke&id=<?php echo $priv->ID(); ?>&c=<?php echo $call->ID(); ?>" title="Remove this privilege">Revoke</a>
							</td>
						</tr>
					<?php endwhile; ?>
					</table>
				</div>
				<a href="#"><i class="fa fa-arrow-up"></i> Top</a>
			</div>

		</div>

		
		<?php include "../includes/footer.php"; ?>
		<?php include "../includes/nav.php"; ?>
	
<script>
$(function()
{
	if (window.location.search.indexOf("granted") == 1)
	{
		if (window.location.hash == "#to-member")
			$.sticky("Granted privilege(s) to member");
		else if (window.location.hash == "#to-calling")
			$.sticky("Granted privilege(s) to calling");
	}
	else if (window.location.search.indexOf("revoked") == 1)
	{
		if (window.location.hash == "#by-member")
			$.sticky("Revoked privilege from member");
		else if (window.location.hash == "#by-calling")
			$.sticky("Revoked privilege from calling");
	}
});
</script>
	</body>
</html>