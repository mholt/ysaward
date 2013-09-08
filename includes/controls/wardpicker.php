<?php

// Build the list of wards by stake
$r = DB::Run("SELECT `ID`, `Name`, `StakeID` FROM `Wards` WHERE `Deleted` != 1 ORDER BY `StakeID`, `Name`");

$stakes = array();

while ($row = mysql_fetch_array($r))
{
	$sid = $row['StakeID'];
	$wid = $row['ID'];

	if (!array_key_exists($sid, $stakes))
		$stakes[$sid] = array();

	$stakes[$sid][] = $wid;
}

?>

<select size="1" name="ward_id" id="wardid">
	<option value="" <?php if (!isset($WARD)) echo 'selected' ?>>Select a ward</option>
<?php
foreach ($stakes as $sid => $wards)
{
	$stakeObj = Stake::Load($sid);
?>
	<optgroup label="<?php echo $stakeObj->Name; ?>">
<?php
	foreach ($wards as $wid)
	{
		// Get the bishop's name, if any.
		$ward = Ward::Load($wid);
		$bishop = $ward->GetBishop();
?>
		<option value="<?php echo $wid; ?>"<?php if (isset($WARD) && $WARD->ID() == $wid) echo 'selected="selected"'; ?>><?php echo $ward->Name; if ($bishop) echo " (Bishop ".$bishop->LastName.")"; ?></option>
<?php
	}
?>
	</optgroup>
<?php
}
?>
</select>