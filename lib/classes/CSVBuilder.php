<?php

/**
 *
 * Builds a text-delimited string.
 * 
 * @author Matthew Holt
 * @version 1.0
 *
**/

class CSVBuilder
{
	private $Delimiter;				// The delimiter to be used
	private $Fields = array();		// The array of field names
	private $Rows = array();		// The array of rows, which are arrays of values

	const DEFAULT_DELIMITER = ",";			// Default delimiter value. Usually a comma.
	const DEFAULT_FILENAME = "file.csv";	// Default filename if output for download.
	const NEW_LINE = "\r\n";				// Newline character, or character sequence.


	/**
	 * Constructor.
	 * @param char $delim (optional) The delimiter to use. Defaults to a comma.
	 */
	public function __construct($delim = self::DEFAULT_DELIMITER)
	{
		$this->SetDelimiter($delim);
	}


	/**
	 * Sets the delimiter to be used when generating the CSV file.
	 * @example Common delimiters are "," (default), "\t" (tab), and "|" (pipe).
	 * @param char $delim The delimiter. Must be a single character. Defaults to ','.
	 *			The delimiter cannot be a double-quote (").
	**/
	public function SetDelimiter($delim = self::DEFAULT_DELIMITER)
	{
		// Don't allow non-string, empty, multi-char,
		// or double-quote delimiters
		if (!is_string($delim) || !strlen($delim)
			|| strlen($delim) > 1 || $delim == '"')
			$delim = self::DEFAULT_DELIMITER;

		$this->Delimiter = $delim;
	}


	/**
	 * Adds a field to the header. All the fields should be added before
	 * any values are added. Otherwise, empty values will be appended to
	 * every row to accomodate the new field added after-the-fact.
	 * @param string $name The name of the field
	 */
	public function AddField($name)
	{
		$this->Fields[] = $this->ToString($name);

		$numFields = count($this->Fields);

		// Rows should have as many values as there are fields
		foreach ($this->Rows as &$row)
			while (count($row) < $numFields)
				$row[] = "";
	}


	/**
	 * Adds an empty value to the current row and goes to the next field.
	 * This function will not let you skip more fields than the number of
	 * blanks left on the end of the current row.
	 * @param int $skipCount (optional) The number of fields to skip. Default 1.
	 */
	public function SkipField($skipCount = 1)
	{
		$rowLength = count($this->Rows[count($this->Rows) - 1]);
		$numFields = count($this->Fields);

		// Can't skip more fields than there are left to fill on the row
		$skipCount = min($skipCount, $numFields - $rowLength);

		for ($i = 0; $i < $skipCount; $i ++)
			$this->AddValue("");
	}

	/**
	 * @todo Implementation
	 *
	 * Insert a value at a certain row for a certain field.
	 * @param int $row The 0-based index of the row to insert into
	 * @param string $field The name of the field under which this value goes
	 * @param string $value The value to insert
	*/
	public function InsertValue($row, $field, $value)
	{
		
	}


	/**
	 * Appends a value to the current row in the next (adjacent) field.
	 * If a value is added to a row causing the row to be longer than the
	 * number of fields, an empty field is added to make room for the value.
	 * @param strin $value The value to insert
	**/
	public function AddValue($value)
	{
		$value = $this->ToString($value);

		if (count($this->Rows) == 0)
			$this->NextRow();

		$numFields = count($this->Fields);
		$lastRowIdx = count($this->Rows) - 1;

		$this->Rows[count($this->Rows) - 1][] = $value;

		// Fields and values should have equal count
		if ($numFields < count($this->Rows[$lastRowIdx]))
			$this->AddField("");
	}


	/**
	 * Moves the iterator to the next row.
	**/
	public function NextRow()
	{
		$this->FillOutLastRow();
		$this->Rows[] = array();
	}


	/**
	 * Generates a string which is the CSV file's contents.
	 * @return The contents of the CSV file as a string
	**/
	public function Generate()
	{
		$this->FillOutLastRow();

		$csv = "";
		$delimLength = strlen($this->Delimiter);

		// First row is field names
		foreach ($this->Fields as $field)
			$csv .= $this->AppendString($field);

		$csv = trim($csv, $this->Delimiter);

		// Start each row with a newline, then add the values
		foreach ($this->Rows as $row)
		{
			$rowString = self::NEW_LINE;

			foreach ($row as $value)
				$rowString .= $this->AppendString($value);
			
			// Trim the trailing comma
			$csv .= substr($rowString, 0, strlen($rowString) - $delimLength);
		}

		return $csv;
	}


	/**
	 * Generates a CSV file and causes the browser to download it.
	 * No output should have been previously sent.
	 * @param string $filename (optional) The name of the file to suggest to the browser
	**/
	public function Download($filename = "file.csv")
	{
		if (!$filename || !is_string($filename))
			$filename = self::DEFAULT_FILENAME;

		$contents = $this->Generate();

		// Forbid caching
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

		// Force browser to download instead of rendering
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Type: text/csv");

		// This suggests a filename and also forces the save dialog, if any
		header("Content-Disposition: attachment; filename={$filename};");

		// Tell the browser this is to be streamed binary since the raw
		// bytes will be sent to the client. Also give the file size in bytes
		// so that a progress bar can be used.
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".strlen($contents));

		// Renders the file
		echo $contents;
	}


	/**
	 * @internal
	 * 
	 * Sanitizes the input and appends the delimiter.
	 * @param string $value The value to sanitize which is being appended.
	 * @return The sanitized value followed by the delimiter.
	**/
	private function AppendString($value)
	{
		return $this->Safe($value) . $this->Delimiter;	// Example: "my value,"
	}

	/**
	 * @internal
	 * 
	 * If the last (current) row doesn't have enough values to
	 * fill out all the columns, this will add empty values to
	 * the row so the end result is a valid format.
	 */
	private function FillOutLastRow()
	{
		$numFields = count($this->Fields);
		$lastRowIdx = count($this->Rows) - 1;

		if ($lastRowIdx > -1)
		{
			// If the row is empty, get rid of it. Otherwise fill it out.
			if (count($this->Rows[$lastRowIdx]) == 0)
				unset($this->Rows[$lastRowIdx]);
			else
				while (count($this->Rows[$lastRowIdx]) < $numFields)
					$this->Rows[$lastRowIdx][] = "";
		}
	}

	/**
	 * @internal
	 *
	 * Makes a string value syntactically safe for insertion into this text-delimited structure.
	 * @param string $str The string to make safe
	 * @return The sanitized string
	**/
	private function Safe($str)
	{
		$str = $this->ToString($str);

		$needsQuotes = (strpos($str, $this->Delimiter) !== false
						|| strpos($str, "\r") !== false
						|| strpos($str, "\n") !== false
						|| strpos($str, '"') !== false
						|| strpos($str, ' ') === 0
						|| strrpos($str, ' ') === strlen($str) - 1);

		if ($needsQuotes)
		{
			// Each instance of double quotes is escaped by "" (two instances)
			$str = str_replace('"', '""', $str);
			$str = '"'.$str.'"';
		}

		$str = trim($str);

		return $str;
	}


	/**
	 * @internal
	 *
	 * Ensures the value passed in will be a string when it comes out.
	 * @param $str The data to test and make a string if it's not already
	 * @return The string version of what was passed in
	 */
	private function ToString($str)
	{
		if (!isset($str) || $str === null)
			return "";

		try
		{
			$str = strval($str);
		}
		catch (Exception $e)
		{
			$str = is_object($str) ? get_class($str) : "";
		}

		return $str;
	}
}

?>