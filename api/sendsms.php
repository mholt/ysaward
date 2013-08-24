<?php
require_once "../lib/common.php";
set_time_limit(0);

// Check arguments first for security
if (count($argv) < 3)
	exit;

$jobid = $argv[1];
$pwd = $argv[2];

if (!$jobid || $pwd != SMS_JOB_PASSWORD)
	fail("No job ID or the password is wrong.\r\n");

// Open DB connection and load the SMSJob
$DB = new DB();
$job = SMSJob::Load($jobid);


// Mark the job as started if not already. If already started or missing info, bail.
if ($job->Started > 0 || !$job->Message || !$job->SenderID || !$job->SenderName || !$job->SenderPhone)
	fail("Job properties incorrect (already started, or missing necessary information.\r\n");


// Get a list of numbers from which we can send on our account with our SMS provider
$numbers = array();
$json = file_get_contents(SMS_META_BASE."/account/numbers?api_key=".urlencode(SMS_API_KEY)."&api_secret=".urlencode(SMS_API_SECRET));
$json_obj = json_decode($json);
foreach ($json_obj->numbers as $num)
	$numbers[] = $num->msisdn;

// Begin
$job->Started = now();
$job->Save();

// Get EUR -> USD exchange rate
$euroToUsd = euroToUsdRate();


// This method of sending is optimized for repeated HTTP requests by opening a TCP pipeline
// and leaving it open as different requests are issued constantly.
$request = new HttpRequest(SMS_API_BASE, HttpRequest::METH_GET);


// The HTTP connection is ready; let's loop through each recipient and send the texts!
$iterations = 0;	// One of our infinite-loop sentry variables
$abort = false;		// Abort flag
$errorCode = "";	// Error code, if aborted
$errorReason = "";	// Error reason, if aborted
$lasti = 0;			// Used in case of abort; lets us know how far we got
for ($i = 0; $i < count($job->Recipients); $i++)
{
	$iterations++;						// Very important that this happens first!
	if ($iterations > 300)				// This cap should be higher than any ward's member count, but not much higher.
	{
		// Alert the webmaster of a possible infinite loop here, or possibly that
		// errors occurred sending to MOST members, causing lots of retries
		$mail = new Mailer();
		$mail->FromAndReplyTo(ERR_HANDLE_FROM_NAME, EMAIL_BLACKHOLE);
		$mail->Subject("Ward website alert: Possible runaway loop during SMS send");
		$mail->Body("SMSJob with ID ".($job->ID())." might have had a runaway, or infinite, loop while sending text messsages.\n\nThere could be a bug in the code or there were many errors while sending to most recipients. The job was terminated.");
		$mail->To(WEBMASTER_NAME, WEBMASTER_EMAIL);
		$mail->Send();
		break;
	}

	$lasti = $i;

	if ($abort)
		break;

	$start = microtime(true);

	$recipID = $job->Recipients[$i]->memberID;
	$recipName = $job->Recipients[$i]->name;
	$recipPhone = $job->Recipients[$i]->number;

	// Make sure we only attempt to send to this member within a throttled number of times;
	// it helps prevent infinite loops in case a failover... well... fails... like, hardcore.
	// This happened once. Oops! One poor guy in the ward got over 3,000 text messages
	// in about an hour. I've since re-written all the logic and added in more failsafes.

	$job->Recipients[$i]->attempts++;
	
	if ($job->Recipients[$i]->attempts > SMS_MAX_ATTEMPTS_PER_RECIPIENT)
	{
		waitIfNeeded($start);
		continue;
	}

	// Rotate, round-robin style, through the available sender numbers
	$senderNumber = $numbers[$iterations % count($numbers)];

	// Apply template values to the message, if any
	$nameArray = explode(" ", trim($recipName));
	if (!$nameArray || count($nameArray) == 0 || !trim($nameArray[0]))
		$nameArray = array($recipName);
	$message = str_replace("{FIRSTNAME}", trim($nameArray[0]), $job->Message);

	// Set up the query's parameters
	$query = array(
		"api_key" => SMS_API_KEY,
		"api_secret" => SMS_API_SECRET,
		"from" => $senderNumber,
		"to" => $recipPhone,
		"text" => $message
	);

	$request->setQueryData($query);

	// Send the text message
	$request->send();

	$response = json_decode($request->getResponseBody());
	
	if (!$job->SegmentCount)
		$job->SegmentCount = $response->{'message-count'};

	// Each segment in the response contains its own cost and potentially an error code
	foreach ($response->messages as $segment)
	{
		if ($segment->status == 0)
		{
			// Successful send.
			// Convert cost from EUR to USD (https://getsatisfaction.com/nexmo/topics/is_the_message_price_thats_returned_from_a_rest_request_in_eur_or_usd)
			$job->Cost += $segment->{'message-price'} * $euroToUsd;
		}
		else
		{
			// Problem sending.
			// Status codes available at: https://docs.nexmo.com/index.php/messaging-sms-api/send-message

			if ($segment->status == 2 || $segment->status == 3 || $segment->status == 4
				|| $segment->status == 8 || $segment->status == 9 || $segment->status == 12
				|| $segment->status == 19 || $segment->status == 20)
			{
				// All of those codes should terminate the whole job because they're all show-stoppers.
				// Status Code 9 is the most likely: "Partner quota exceeded," meaning:
				// "Your pre-pay account does not have sufficient credit to process this message"

				// Alert the webmaster via email because this will require attention.
				$mail = new Mailer();
				$mail->FromAndReplyTo(ERR_HANDLE_FROM_NAME, EMAIL_BLACKHOLE);
				$mail->Subject("Ward website alert: SMS provider returned fatal error during send");
				$mail->Body("While sending a text message from the ward website:\n\nStatus Code {$segment->status}\n\nReason:".($segment->{'error-text'})."\n\nJob ID ".($job->ID())." ended unexpectedly.");
				$mail->To(WEBMASTER_NAME, WEBMASTER_EMAIL);
				$mail->Send();

				$errorCode = $segment->status;
				$errorReason = $segment->{'error-text'};
				$abort = true;
				break;
			}
			else if ($job->Recipients[$i]->attempts >= SMS_MAX_ATTEMPTS_PER_RECIPIENT)
			{
				// Can't try anymore for this recipient; already tried maximum number of times.
				$job->AddFailedRecipient($recipID, $recipName, $recipPhone, $segment->status, $segment->{'error-text'});
				break;
				
				/*
					NOTE: At some point, something changed with Nexmo which doesn't allow multi-segment (concatenated)
					messages to US carriers, and each one would get a "throttled" error. They used to automatically
					split the message up and send them in segments for us. (I suspect this breaking change, which
					many customers complained about, is what caused the infamous infinite loop described above.)

					UPDATE: They've resolved this, at least for now, so concatenated messages work okay again.
					If it ever stops working, you'll get those throttling errors for each segmented message,
					and if it's not something Nexmo can/will fix, you'll have to split the messages into segments manually.
				*/
			}
			else
			{
				if ($segment->status == 1)
				{
					// "Throttled"
					// "You have exceeded the submission capacity allowed on this account, please back-off and retry"
					millisleep(SMS_BETWEEN_MESSAGES * 1.5);	// With extra time padding just in case
					$i --;
					break;
				}
			}
		}
	}

	if ($abort)
		break;

	waitIfNeeded($start);
}

if ($abort)
{
	for ($i = $lasti; $i < count($job->Recipients); $i++)
		$job->AddFailedRecipient($job->Recipients[$i]->memberID, $job->Recipients[$i]->name, $job->Recipients[$i]->number, $errorCode, $errorReason." (job terminated safely)");
}


// Finish
DB::Run("UPDATE Wards SET Balance = Balance - {$job->Cost} WHERE ID={$job->WardID} LIMIT 1");
$job->NumbersUsed = json_encode($numbers);
$job->Finished = now();
$job->Save();

exit;


function waitIfNeeded($start)
{
	// Wait a certain amount of time before going to the next message, if necessary
	$end = microtime(true);
	$duration = $end - $start;
	if ($duration * 1000 < SMS_MS_BETWEEN_MESSAGES)
		millisleep(SMS_MS_BETWEEN_MESSAGES - $duration + 50);	// add a short duration for integrity against network latency
}
?>