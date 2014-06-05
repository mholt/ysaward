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
			<?php if ($MEMBER && !$LEADER): ?>
			<br>
			<span class="quick-links">
				<a href="/profile">Update profile</a>
				&bull;
				<a href="/survey">Update survey answers</a>
			</span>
			<?php endif; ?>
		</span>
<?php endif; ?>
	</div>
	<div class="half-side text-right nowrap" style="padding-right: 0px;">
<?php if (!IS_MOBILE && strpos($_SERVER['REQUEST_URI'], "/directory") !== false): ?>
		<a href="/directory/download" class="fa fa-download fa-lg"></a>
		&nbsp; &nbsp;
		<a href="/directory/print" class="fa fa-print fa-lg" target="_blank"></a>
<?php endif; ?>

		<a href="javascript:" id="menu-icon">
			Menu
			<i class="fa fa-bars"></i>
		</a>
	</div>
</header>