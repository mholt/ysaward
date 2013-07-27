<?php

/**
 * @author Matthew Holt
 */

// Ad-hoc "enum"

final class QuestionType
{
	const FreeResponse = 0;			// Textarea (anything goes)
	const MultipleChoice = 1;		// Radio (select one only) buttons
	const MultipleAnswer = 2;		// Checkbox (more-than-one) answer
	const YesNo = 3;				// Yes or No values
	const ScaleOneToFive = 4;		// Value from 1 to 5
	const Timestamp = 5;			// MySQL Timestamp format
	const CSV = 6;					// List of items (not necessarily
									// comma-separated values)
	
	// No constructing allowed! That would be weird, anyway...
	private function __construct()
	{
		return null;
	}
}

?>