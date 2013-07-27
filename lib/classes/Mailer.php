<?php
require_once DOCROOT."/lib/classes/class.phpmailer.php";

/**
 * @author Matthew Holt
 *
 * Simple wrapper for the PHPMailer class we're using to send emails.
 */


class Mailer
{
	private $Mailer;
	private $From = array();
	private $Subject = "";
	private $To = array();
	private $FailedRecipients = array();
	const MILLISECONDS_BETWEEN_MESSAGES = 500;

	public function __construct()
	{
		// Construct our mailer object.
		$this->Mailer = new PHPMailer(true);	// true = throw exceptions on errors
		
		// Use SMTP
		$this->Mailer->IsSMTP();

		// SMTP host. We're using Amazon SES.
		$this->Mailer->Host = SMTP_HOST;

		// Use authentication.
		$this->Mailer->SMTPAuth = true;

		// Use TLS to encrypt the connection
		$this->Mailer->SMTPSecure = "tls";

		// Set the port. 25 = regular, 465 = older secure/TLS port, and 587 = newer port standard which requires authentication
		$this->Mailer->Port = 587;

		// Method for sending mail. Use SMTP.
		$this->Mailer->Mailer = "smtp";

		// Keep the SMTP connection alive: don't re-create it for each call to Send()
		$this->Mailer->SMTPKeepAlive = true;

		// Turn debug off. This is default anyway.
		$this->Mailer->SMTPDebug = false;

		// SMTP username. For Amazon SES, use the IAM username
		$this->Mailer->Username = AWS_IAM_USERNAME;

		// SMTP password. For Amazon SES, use the IAM password
		$this->Mailer->Password = AWS_IAM_PASSWORD;

		// Establish DKIM credentials. This goes beyond the Amazon SES DKIM stuff so
		// that ISPs and mail servers know that not only was the email sent via Amazon SES,
		// but that it indeed originated from us. Generated these on: http://dkim.worxware.com/createkeys.php
		// More info: http://www.lessannoyingcrm.com/articles/255/Sending_verified_emails_through_Amazon_Simple_Email_Service_with_DKIM_and_PHPMailer
		$this->Mailer->DKIM_domain = DKIM_DOMAIN;
		$this->Mailer->DKIM_private = DKIM_PRIVATE_KEY;
		$this->Mailer->DKIM_selector = DKIM_SELECTOR;		// As found in the DNS record (TXT entry)
		$this->Mailer->DKIM_passphrase = DKIM_PASSPHRASE;

		// This is false by default but can be true later (I set this here for explicitness, but it's not required)
		$this->Mailer->IsHTML(false);
	}

	function __destruct()
	{
		// Very important since we're using SMTPKeepAlive
		$this->Mailer->SmtpClose();
	}

	public function FromAndReplyTo($name, $email)
	{
		$this->From = array("name" => $name, "email" => $email);
		$this->Mailer->SetFrom($email, $name, true);
	}

	public function From($name, $email)
	{
		$this->From = array("name" => $name, "email" => $email);
		$this->Mailer->SetFrom($email, $name, false);
	}

	public function ClearReplyTo()
	{
		$this->Mailer->ClearReplyTos();
	}

	public function ReplyTo($name, $email)
	{
		$this->Mailer->ClearReplyTos();
		$this->Mailer->AddReplyTo($email, $name);
	}

	public function AddReplyTo($name, $email)
	{
		$this->Mailer->AddReplyTo($email, $name);
	}

	public function IsHTML($isHTML)
	{
		$this->Mailer->IsHTML($isHTML);
	}

	public function Subject($subject)
	{
		// TODO: Handle the "subject changed" thing better, where we "Cc:" the user... maybe?
		$this->Subject = $subject;
		$this->Mailer->Subject = $subject;
	}

	public function Body($body)
	{
		$this->Mailer->Body = $body;
	}

	public function To($name, $email)
	{
		$this->To = array(array("name" => $name, "email" => $email));
	}

	public function AddTo($name, $email)
	{
		$this->To[] = array("name" => $name, "email" => $email);
	}

	// Expected $array format: [ ["name" => $name, "email" => $email], ... ]
	public function RecipientArray($array)
	{
		$this->To = $array;
	}

	/*

	// These are disabled because of the way we send messages. We send messages
	// one at a time using the "To" field since, in testing, sending a batch
	// of "BCC" wasn't really any faster.
	// In other words, using these functions when we don't have to don't make a lot of sense.

	public function AddBCC($name, $email)
	{
		$this->Mailer->AddBCC($email, $name);
	}

	public function AddCC($name, $email)
	{
		$this->Mailer->AddCC($email, $name);
	}

	*/

	// Sends the emails, but clears the list of "To" recipients when it's done!
	// Also clears the list of FailedRecipients when it begins
	public function Send()
	{
		set_time_limit(0);
		$this->FailedRecipients = array();

		foreach ($this->To as $recip)
		{
			$start = microtime(true);	// Seconds since Unix epoch, precise to the nearest millionth (microsecond)

			// Add the next recipient (we send one email at a time)
			$this->Mailer->AddAddress($recip['email'], $recip['name']);

			// If this is going to the sender, make the "Cc" note
			$subjectModified = false;
			if ($recip['email'] == $this->From['email'])	// TODO: From is not the sender's email, we need to be using REPLY-TO...
			{
				$this->Mailer->Subject = "Cc: ".$this->Subject;
				$subjectModified = true;
			}

			try
			{
				// Send the message
				$this->Mailer->Send();
			}
			catch (Exception $e)
			{
				// '$mail->ErrorInfo' gets even more information about the error than may be in the exception.
				// For now, we don't use that (stupid, I know) -- just add this recipient to the list of failed
				// recipients and try the next one. At least we'll know who didn't get the message.
				// And this echo statement should print something to the error_log...
				echo $this->Mailer->ErrorInfo."\r\n";
				$this->FailedRecipients[] = $recip;
			}
			
			// VERY IMPORTANT! (Only send to one recipient at a time.)
			// Unfortunately, I've had issues sending Bcc-only emails with SES...
			// I get this error: SMTP Error: Data not accepted.
			// As a work-around, I send one email to each recipient at a time with "To"
			// while keeping the SMTP connection alive. So between 
			// each email I need to clear the "To" address.
			$this->Mailer->ClearAddresses();
			
			// If we modified the subject, change it back
			if ($subjectModified)
				$this->Mailer->Subject = $this->Subject;
			
			$end = microtime(true);
			$duration = $end - $start;

			// Wait a certain amount of time before going to the next message, if necessary
			if ($duration * 1000 < self::MILLISECONDS_BETWEEN_MESSAGES)
				millisleep(self::MILLISECONDS_BETWEEN_MESSAGES - $duration);
		}

		// Clear the "To" array
		$this->To = array();
	}

	public function FailedRecipients()
	{
		return $this->FailedRecipients;
	}
}

?>