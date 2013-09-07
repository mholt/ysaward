<header>
	<div class="half-side nowrap">
		<a href="/directory"><img src="<?php echo SITE_SMALL_IMG; ?>" class="logo"></a>
<?php if (!IS_MOBILE): ?>
		&nbsp; &nbsp;
		<span class="header-text">
			<b><a href="/profile"><?php echo $USER->FirstName." ".$USER->LastName; ?></a></b>
			&mdash;
			<?php echo $WARD->Name; ?> Ward
		</span>
<?php endif; ?>
	</div>
	<div class="half-side text-right nowrap" style="padding-right: 0px;">
		<a href="javascript:" id="menu-icon">
			Menu
			<i class="icon-reorder icon-large"></i>
		</a>
	</div>
</header>