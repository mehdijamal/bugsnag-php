# Bugsnag Client for PHP

## How To Install

Via [Composer](http://getcomposer.org):

	"require": {
		"bugsnag/notifier": "1.0.0"
	}

## Sending Exceptions To Bugsnag

	$notifier = new Bugsnag\Notifier\Client($apiKey);

	$notifier->notify($exception, $metaData);


## Setting Notifier Options

	$notifier->setUserId(1)
	         ->setReleaseStage('testing')
	         ->setAppVersion('1.0.0')
	         ->setOsVersion('1.0.0')
	         ->setContext('home#index')
	         ->useSSL();