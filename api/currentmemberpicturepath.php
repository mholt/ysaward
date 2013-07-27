<?php
require_once("../lib/init.php");
protectPage();

$m = Member::Current();

Response::Send(200, $m->PictureFile());

?>