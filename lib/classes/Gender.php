<?php

/**
 * @author Matthew Holt
 */

// Ad-hoc "enum"

final class Gender
{
	const Male = 1;
	const Female = 2;

	// Usually my functions with "Render" actually output
	// to the buffer (screen), but in this case,
	// Gender::Render sounded cooler than Gender::Display.
	// Returns "Male" or "Female"
	public static function Render($gender)
	{
		if ($gender != Gender::Male && $gender != Gender::Female)
			return '';

		return $gender == Gender::Male ? 'Male' : 'Female';
	}

	// Returns the LDS version of Gender, "Brother" or "Sister".
	public static function RenderLDS($gender)
	{
		if ($gender != Gender::Male && $gender != Gender::Female)
			return '';

		return $gender == Gender::Male ? 'Brother' : 'Sister';
	}

	public static function Pronoun($gender)
	{
		return $gender == Gender::Male ? 'him' : 'her';
	}

	public static function PossessivePronoun($gender)
	{
		return $gender == Gender::Male ? 'his' : 'her';
	}

	// No constructing allowed! That would be weird, anyway...
	private function __construct()
	{
		return null;
	}
}

?>