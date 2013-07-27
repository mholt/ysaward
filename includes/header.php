<header>
	<nav class="grid-12">
		<ul class="g-12 group">
			<li id="logo-item">
				<a href="/directory.php" id="logo-link">
					<img src="<?php echo SITE_SMALL_IMG; ?>" id="logo">
				</a>
			</li>
			<li>
				<a href="/directory.php"<?php if (PATH == "/directory.php" || PATH == "/exportmls.php") echo ' class="current"'; ?>>Directory &nbsp;&#9662;</a>
				<ul class="dropdown">
					<li><a href="/directory.php">View Online</a></li>
					<li><a href="/print.php" target="_blank">Print</a></li>
					<li><a href="/csv.php">Download</a></li>
					<?php if ($MEMBER && $MEMBER->HasPresetCalling()): ?><li><a href="/exportmls.php">Export to MLS</a></li><?php endif; ?>
				</ul>
			</li>
			
			
			<?php if ($MEMBER): ?>
			<li><a href="/callings.php"<?php if (PATH == "/callings.php") echo ' class="current"'; ?>>Callings</a></li>
			<li>
				<a href="javascript:" target="_blank" <?php if (PATH == "/email.php" || PATH == "/sms.php") echo ' class="current"'; ?>>Send &nbsp;&#9662;</a>
				<ul class="dropdown">
					<li><a href="/email.php">Email</a></li>
					<li><a href="/sms.php">Text (SMS)</a></li>
				</ul>
			</li>
			<li>
				<a href="/fhe.php"<?php if (PATH == "/fhe.php") echo ' class="current"'; ?>>FHE Groups</a>
			</li>
			<!--<li><a href="https://www.facebook.com/groups/20thward/" target="_blank">Facebook page</a></li>-->
			<?php endif; ?>
			<li>
				<a href="https://www.lds.org/tools/" target="_blank">LDS.org &nbsp;&#9662;</a>
				<ul class="dropdown">
					<li><a href="https://www.lds.org/directory/" target="_blank">Directory</a></li>
					<li><a href="https://www.lds.org/lesson/" target="_blank">Lesson schedules</a></li>
					<li><a href="https://www.lds.org/church-calendar/" target="_blank">Calendar</a></li>
					<li><a href="https://www.lds.org/member-news/" target="_blank">Newsletter</a></li>
					<li><a href="https://www.lds.org/study-tools/" target="_blank">Study Tools</a></li>
					<li><a href="https://www.lds.org/rcmaps/" target="_blank">Maps</a></li>
					<li><a href="https://mormonorg.lds.org/profile" target="_blank">Mormon.org profile</a></li>
					<li><a href="https://familysearch.org" target="_blank">FamilySearch</a></li>
					<li><a href="https://familysearch.org/volunteer/indexing" target="_blank">Indexing</a></li>
				</ul>
			</li>
			
			
			<?php if ($MEMBER && $MEMBER->HasAnyManagePrivilege()): ?>
			<li>
				<a href="/manage"<?php if (strpos(PATH, "/manage") !== false) echo ' class="current"'; ?>>Manage &nbsp;&#9662;</a>
				<ul class="dropdown">
					<?php if ($MEMBER->HasPrivilege(PRIV_MNG_FHE)): ?><li><a href="/manage/fhe.php">FHE groups</a></li><?php endif; ?>
					<?php if ($MEMBER->HasPrivilege(PRIV_MNG_SURVEY_QU)): ?><li><a href="/manage/survey.php">Survey Questions</a></li><?php endif; ?>
					<?php if ($MEMBER->HasPrivilege(PRIV_MNG_SURVEY_PER)): ?><li><a href="/manage/permissions.php">Survey Permissions</a></li><?php endif; ?>
					<?php if ($MEMBER->HasPrivilege(PRIV_MNG_SITE_PRIV)): ?><li><a href="/manage/privileges.php">Site Privileges</a></li><?php endif; ?>
					<?php if ($MEMBER->HasPrivilege(PRIV_MNG_CALLINGS)): ?><li><a href="/manage/callings.php">Callings</a></li><?php endif; ?>
					<?php if ($MEMBER->HasPrivilege(PRIV_MNG_PROFILE_PICS)): ?><li><a href="/manage/profilepictures.php">Profile Pictures</a></li><?php endif; ?>
					<?php if ($MEMBER->HasPrivilege(PRIV_DELETE_ACCTS)): ?><li><a href="/manage/prune.php">Delete Accounts</a></li><?php endif; ?>
				</ul>
			</li>
			<?php endif; ?>
			
			
			<?php if ($MEMBER != null && $LEADER == null): ?>
			<li id="user">
				<a href="/member.php?id=<?php echo $MEMBER->ID(); ?>" id="user-link" title="View your profile"<?php if (PATH == "/editprofile.php" || PATH == "/answers.php") echo ' class="current"'; ?>>
					<img src="<?php echo $MEMBER->PictureFile(true); ?>">&nbsp;
					<?php echo $MEMBER->FirstName; ?> <?php echo $MEMBER->LastName; ?> &nbsp;&#9662;
				</a>
				<ul class="dropdown">
					<li style="padding: 4px 3px; font-size: 12px;"><?php echo $MEMBER->Email; ?></li>
					<li><a href="/editprofile.php">Edit profile</a></li>
					<li><a href="/answers.php">My survey answers</a></li>
					<li><a href="/logout.php">Logout</a></li>
				</ul>
			</li>
			<li id="wd">
				<b>Ward:</b> <?php echo $WARD->Name; ?>
			</li>
			
			
			<?php elseif ($MEMBER == null && $LEADER != null): ?>
			
			
			<li id="user">
				<a href="/" id="user-link" title="Back to main page">
					<?php echo $LEADER->FirstName; ?> <?php echo $LEADER->LastName; ?> &nbsp;&#9662;
				</a>
				<ul class="dropdown" style="margin-top: 0;">
					<li style="padding: 4px 3px; font-size: 12px;"><?php echo $LEADER->Email; ?></li>
					<li><a href="/logout.php">Logout</a></li>
				</ul>
			</li>
			<li id="wd" style="padding-top: 0;">
				<b>Ward:</b>
				<a href="/directory.php" id="user-link" title="Change ward" style="padding-left: 3px;">
					<?php echo $WARD->Name; ?> &nbsp;&#9662;
				</a>
				<ul class="dropdown" style="margin-top: 0;">
					<?php
					// Show list of other wards they can view
					$wardsQuery = DB::Run("SELECT Name, ID FROM Wards WHERE StakeID='{$LEADER->StakeID}' AND Deleted != 1 ORDER BY Name ASC");
					while ($wardRow = mysql_fetch_array($wardsQuery)):
					?>
						<?php if ($wardRow['ID'] != $WARD->ID()): ?>
						<li><a href="changeward.php?id=<?php echo $wardRow['ID']; ?>"><?php echo $wardRow['Name']; ?></a></li>
						<?php else: ?>
						<li style="padding: 2px;"><?php echo $wardRow['Name']; ?></li>
						<?php endif; ?>
					<?php endwhile; ?>
				</ul>
			</li>
			<?php endif; ?>
			
			
		</ul>
		
	</nav>
</header>