<?php
require_once("../../lib/init.php");
protectPage(11);

@ $name = $_POST['name'];

if (!$name || strlen(trim($name)) < 2)
	fail("Please submit a valid name for this calling.");

$c = new Calling($name, $MEMBER->WardID);
if ($c->Save())
	Response::Send(200, $c->ID());
else
	fail("Something bad happened... hmm...");
?>