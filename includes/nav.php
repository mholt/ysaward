<nav>
<?php if ($MEMBER != null && $LEADER == null): ?>
	<b>Me</b>
	<a href="/profile"><i class="fa fa-pencil"></i>Edit profile</a>
	<a href="/survey"><i class="fa fa-edit" style="margin-right: .45em;"></i>Edit survey</a>

	<b>Membership</b>
	<a href="/directory"><i class="fa fa-list-alt"></i>Directory</a>
	<?php if ($MEMBER && $MEMBER->HasPresetCalling()): ?>
	<a href="/exportmls"><i class="fa fa-share"></i>Export to MLS</a>
	<?php endif; ?>
	<a href="/fhe"><i class="fa fa-group"></i>FHE groups</a>
	<a href="/callings"><i class="fa fa-sitemap"></i>Callings</a>

	<b>Send</b>
	<a href="/sms"><i class="fa fa-comments"></i>Texts</a>
	<a href="/email"><i class="fa fa-envelope"></i>Emails</a>

	<!--
	<b>LDS.org</b>
	<a href="#">Directory</a>
	<a href="#">Study tools</a>
	<a href="#">Maps</a>
	<a href="#">Indexing</a>
	<a href="#">Mormon.org profile</a>
	-->

<?php if ($MEMBER && $MEMBER->HasAnyManagePrivilege()): ?>
	<b>Manage</b>
	<?php if ($MEMBER->HasPrivilege(PRIV_MNG_FHE)): ?><a href="/manage/fhe">FHE groups</a><?php endif; ?>
	<?php if ($MEMBER->HasPrivilege(PRIV_MNG_SURVEY_QU)): ?><a href="/manage/survey">Survey Questions</a><?php endif; ?>
	<?php if ($MEMBER->HasPrivilege(PRIV_MNG_SURVEY_PER)): ?><a href="/manage/permissions">Survey Permissions</a><?php endif; ?>
	<?php if ($MEMBER->HasPrivilege(PRIV_MNG_SITE_PRIV)): ?><a href="/manage/privileges">Site Privileges</a><?php endif; ?>
	<?php if ($MEMBER->HasPrivilege(PRIV_MNG_CALLINGS)): ?><a href="/manage/callings">Callings</a><?php endif; ?>
	<?php if ($MEMBER->HasPrivilege(PRIV_MNG_PROFILE_PICS)): ?><a href="/manage/profilepictures">Profile Pictures</a><?php endif; ?>
	<?php if ($MEMBER->HasPrivilege(PRIV_DELETE_ACCTS)): ?><a href="/manage/prune">Delete Accounts</a><?php endif; ?>
<?php endif; ?>

<?php elseif ($MEMBER == null && $LEADER != null): ?>
	<b>Wards</b>
	<?php
	// Show list of other wards they can view
	$wardsQuery = DB::Run("SELECT Name, ID FROM Wards WHERE StakeID='{$LEADER->StakeID}' AND Deleted != 1 ORDER BY Name ASC");
	while ($wardRow = mysql_fetch_array($wardsQuery)):
	?>
		<a href="/api/changeward?id=<?php echo $wardRow['ID']; ?>"><i class="fa fa-asterisk"></i><?php echo $wardRow['Name']; ?></a></li>
	<?php endwhile; ?>

	<b>Membership</b>
	<a href="/directory?stake"><i class="fa fa-list-alt"></i>Stake Directory</a>
	
	<b>Send</b>
	<a href="/sms"><i class="fa fa-comments"></i>Texts</a>
	<a href="/email"><i class="fa fa-envelope"></i>Emails</a>
<?php endif; ?>
	
	<br>
	<a href="/logout"><i class="fa fa-sign-out"></i>Log out</a>


</nav>