<?php
require_once("../../lib/init.php");
protectPage(13);

@ $year = $_POST['year'];
@ $month = $_POST['month'];
@ $day = $_POST['day'];
@ $msg = $_POST['msg'];

if (!$year || !$month || !$day)
	Response::Send(400, "Please select a month, day, and year to terminate accounts");


// Make sure date is far enough in the future (at least 24 hours -- not 1 day, but 24 hours)
exit;

?>