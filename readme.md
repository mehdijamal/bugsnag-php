# Bugsnag Client for PHP

## How To Install

Via [Composer](http://getcomposer.org):

	"require": {
		"bugsnag/notifier": "1.0.0"
	}

## Sending PHP Exceptions To Bugsnag

	$notifier = new Bugsnag\Notifier\PhpClient($apiKey);

	$notifier->notify($exception, $metaData);

## Setting Notifier Options

	$notifier->setUserId(1)
	         ->setReleaseStage('testing')
	         ->setAppVersion('1.0.0')
	         ->setOsVersion('1.0.0')
	         ->setContext('home#index')
	         ->useSSL();

## Sending JavaScript Exceptions To Bugsnag

This is tested using the following [jQuery Client Side Logging](https://github.com/remybach/jQuery.clientSideLogging) plugin.

	$notifier = new Bugsnag\Notifier\JsClient($apiKey);

	$msg = json_decode($_GET['msg']);

	$exception = array(
		'type' = $_GET['type'],
		'message' = $msg->message,
		'line' = $msg->line,
		'file' = $msg->file
	);

	unset($msg->message, $msg->line, $msg->file);

	foreach ($msg as $key => $item)
	{
		$message[$key] = $item;
	}

	$notifier->notify($exception, array('msg' => $message));
