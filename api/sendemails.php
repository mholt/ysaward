<?php
require_once "../lib/common.php";
//date_default_timezone_set('America/Denver');	// Set the current timezone so PHP doesn't whine...

// We can't "protectPage()" or even include init.php because this is called
// and run from the command line, not from a client machine. (no $_SERVER var, etc...)
// Instead, we'll check for a password parameter passed in and include files manually.
// Remember, output of this file goes to /dev/null...

// Check arguments first for security
$jobid = $argv[1];
$pwd = $argv[2];

if (!$jobid || $pwd != EMAIL_JOB_PASSWORD)
	fail("No job ID or the password is wrong.\r\n");


// Open DB connection and load the EmailJob
$DB = new DB();
$job = EmailJob::Load($jobid);


// Using AWS Simple Email Service (SES) we can currently send up to 5 emails per second
// with a max of 10,000 per 24-hour period. We'll establish the SMTP connection with
// PhpMailer and send one email at a time at 500ms intervals (no faster). This serves a dual purpose:
// We are well within our sending boundaries with SES, and the user can't send too many
// emails all at once.

// NOTE: In testing, sending the first message took almost 2 seconds (because it also establishes
// the SMTP connection) -- each message after that took about 710-750ms (about .71-.75 seconds)
// which is slower than I want (but if this becomes an issue maybe we can spawn up multiple threads,
// after our sending limit is increased). Anyway, I get the same performance if using mass BCC in one email.
// Currently, these actual rates mean we can send about 4,500 emails in an hour. An email to an entire
// ward with, say, 160 members, would take just over 2 minutes. Previously, at 1 per second, it would take just under 3 minutes.

// TODO: If we are seeing a high volume of emails:
//  - Spawn up other threads to handle a single job, if the job is large enough, like stake-wide. Deal with concurrency. Yay...
//  - See if 2 or more other jobs are running; if so, wait time before trying. Run anyway after a certain length of time.

// Mark the job as started if not already. If already started or missing info, bail.
if ($job->Started > 0 || !$job->Subject || !$job->Message || !$job->SenderName() || !$job->SenderEmail())
	fail("Job properties incorrect (already started, or missing necessary information.\r\n");

// Ready... set... go!
$job->Started = now();
$job->Save();


// Prepare and send the emails
$mail = new Mailer();
$mail->IsHTML($job->IsHTML);
$mail->From($job->SenderName(), EMAIL_FROM);
$mail->ReplyTo($job->SenderName(), $job->SenderEmail());
$mail->Subject($job->Subject);
$mail->Body($job->Message);
$mail->RecipientArray($job->RecipientsAsArray());

// Send the messages!
$mail->Send();


// Store the list of those who didn't receive the email for whatever reason
$failedRecipients = $mail->FailedRecipients();
foreach ($failedRecipients as $recip)
{
	$job->AddFailedRecipient($recip['name'], $recip['email']);
}


// This deed is done.
$job->Ended = now();
$job->Save();

exit;
?>