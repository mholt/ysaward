<?php
require_once "lib/init.php";

if (IS_MOBILE)
	require "pages/mobile/directory.php";
else
	require "pages/desktop/directory.php";

?>