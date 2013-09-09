<header>
	<div class="half-side nowrap">
		<a href="/directory"><img src="<?php echo SITE_SMALL_IMG; ?>" class="logo"></a>
<?php if (!IS_MOBILE): ?>
		&nbsp; &nbsp;
		<span class="header-text">
			<b>
				<?php if (!$LEADER): ?><a href="/member?id=<?php echo $MEMBER->ID(); ?>"><?php endif; ?><?php echo $USER->FirstName." ".$USER->LastName; ?><?php if (!$LEADER): ?></a><?php endif; ?>
			</b>
			&mdash;
			<?php echo $WARD->Name; ?> Ward
			<br>
			<span class="quick-links">
				<a href="/profile">Update profile</a>
				&bull;
				<a href="/survey">Update survey answers</a>
			</span>
		</span>
<?php endif; ?>
	</div>
	<div class="half-side text-right nowrap" style="padding-right: 0px;">
<?php if (strpos($_SERVER['REQUEST_URI'], "/directory") !== false): ?>
		<a href="/directory/download" class="icon-download-alt icon-large"></a>
		&nbsp; &nbsp;
		<a href="/directory/print" class="icon-print icon-large" target="_blank"></a>
<?php endif; ?>

		<a href="javascript:" id="menu-icon">
			Menu
			<i class="icon-reorder icon-large"></i>
		</a>
	</div>
</header>