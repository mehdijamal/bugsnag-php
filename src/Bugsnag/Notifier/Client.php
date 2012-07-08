<?php namespace Bugsnag\Notifier;

class Client {

	/**
	 * The Bugsnag application API key.
	 *
	 * @var string
	 */
	protected $apiKey;

	/**
	 * Indicates if notifications should be sent over SSL.
	 *
	 * @var bool
	 */
	protected $ssl;

	/**
	 * The base user data for an event.
	 *
	 * @var array
	 */
	protected $baseData = array();

	/**
	 * The HTTP client instance.
	 *
	 * @var Guzzle\Http\Client
	 */
	protected $http;

	/**
	 * The release stages notifications should be sent for.
	 *
	 * @var array
	 */
	protected $notifyStages = array('production');

	/**
	 * Create a new notifier client instance.
	 *
	 * @param  string  $apiKey
	 * @param  bool    $useSSL
	 * @return void
	 */
	public function __construct($apiKey, $useSSL = false)
	{
		$this->ssl = $useSSL;
		$this->apiKey = $apiKey;
		$this->http = new \Guzzle\Http\Client;
	}

	/**
	 * Notify Bugsnag of an exception.
	 *
	 * @param  Exception|array  $exception
	 * @return void
	 */
	public function notify($exception)
	{
		$payload = $this->buildPayload($exception);

		$headers = array('Content-Type' => 'application/json');

		$request = $client->post($this->getApiUrl(), $headers, $payload);

		try {
			$request->send();
		} catch (\Guzzle\Http\Exception\BadResponseException $guzzleException) {
			$this->handleBadResponse($guzzleException->getResponse());
		}
	}

	/**
	 * Build the Bugsnag payload for an exception.
	 *
	 * @param  Exception|array  $e
	 * @return string
	 */
	protected function buildPayload($exception, array $metaData)
	{
		if ( ! is_array($e)) {
			$e = array($e);
		}

		$payload = array('apiKey' => $this->apiKey, 'metaData' => $metaData);

		$payload['notifier'] = $this->getNotifierInfo();

		$event = $this->baseData;

		foreach ($exception as $e) {
			$event['exceptions'][] = $this->buildExceptionArray($e);
		}

		$payload['events'][] = $event;

		return json_encode($payload);
	}

	/**
	 * Build the payload array for a specific exception instance.
	 *
	 * @param  Exception  $e
	 * @return array
	 */
	protected function buildExceptionArray(\Exception $e)
	{
		$payload = array('errorClass' => get_class($e), 'errorMessage' => $e->getMessage());

		foreach ($e->getTrace() as $trace) {
			$traceDetail = array(
				'file' => $trace['file'], 'lineNumber' => $trace['line'], 'method' => $trace['function']
			);

			$payload['stacktrace'][] = $traceDetail;
		}

		return $payload;
	}

	/**
	 * Handle a bad response from Bugsnag.
	 *
	 * @param  Exception $e
	 * @param  Guzzle\Http\Message\Response  $response
	 * @return void
	 */
	protected function handleBadResponse(\Exception $e, \Guzzle\Http\Message\Response $response)
	{
		$status = $response->getStatusCode();

		switch ($status) {
			case 400:
				throw new BadRequestException("The payload failed syntax validation.");
			case 401:
				throw new UnauthorizedException("The given API key is invalid.");
			case 413:
				throw new RequestTooLargeException("The payload is too large to process.");
			case 429:
				throw new TooManyRequestsException("The payload was not processed due to rate limiting.");
			default:
				throw $e;
		}
	}

	/**
	 * Get the API key.
	 *
	 * @return string
	 */
	public function getApiKey()
	{
		return $this->apiKey;
	}

	/**
	 * Set the value of the API key.
	 *
	 * @param  string  $apiKey
	 * @return Bugsnag\Notifier\Client
	 */
	public function setApiKey($apiKey)
	{
		$this->apiKey = $apiKey;

		return $this;
	}

	/**
	 * Set the unique identifier for the application user.
	 *
	 * @param  mixed  $value
	 * @return Bugsnag\Notifier\Client
	 */
	public function setUserId($value)
	{
		$this->baseData['userId'] = $value;

		return $this;
	}

	/**
	 * Set the application version.
	 *
	 * @param  string  $value
	 * @return Bugsnag\Notifier\Client
	 */
	public function setAppVersion($value)
	{
		$this->baseData['appVersion'] = $value;

		return $this;
	}

	/**
	 * Set the operating system version.
	 *
	 * @param  string  $value
	 * @return Bugsnag\Notifier\Client
	 */
	public function setOsVersion($value)
	{
		$this->baseData['osVersion'] = $value;

		return $this;
	}

	/**
	 * Set the application release stage.
	 *
	 * @param  string  $value
	 * @return Bugsnag\Notifier\Client
	 */
	public function setReleaseStage($value)
	{
		$this->baseData['releaseStage'] = $value;

		return $this;
	}

	/**
	 * Set the release stages that notifications should be sent for.
	 *
	 * @param  array  $stages
	 * @return Bugsnag\Notifier\Client
	 */
	public function setNotifyReleaseStages(array $stages)
	{
		$this->notifyStages = $stages;

		return $this;
	}

	/**
	 * Set the application context.
	 *
	 * @param  string  $value
	 * @return Bugsnag\Notifier\Client
	 */
	public function setContext($value)
	{
		$this->baseData['context'] = $value;

		return $this;
	}

	/**
	 * Indicate that notifications should be sent over SSL.
	 *
	 * @return Bugsnag\Notifier\Client
	 */
	public function useSSL()
	{
		$this->ssl = true;
	}

	/**
	 * Get the HTTP client instance.
	 *
	 * @return Guzzle\Http\Client
	 */
	public function getHttpClient()
	{
		return $this->http;
	}

	/**
	 * Set the HTTP client instance.
	 *
	 * @param  Guzzle\Http\Client  $client
	 * @return Bugsnag\Notifier\Client
	 */
	public function setHttpClient(\Guzzle\Http\Client $client)
	{
		$this->http = $client;

		return $this;
	}

	/**
	 * Get the Bugsnag API base URL.
	 *
	 * @return string
	 */
	protected function getApiUrl()
	{
		$prefix = $this->ssl ? 'https://' : 'http://';

		return $prefix.'notify.bugsnag.com';
	}

	/**
	 * Get information about the notification library.
	 *
	 * @return array
	 */
	public function getNotifierInfo()
	{
		return array('name' => 'Bugsnag PHP', 'version' => '1.0.0', 'url' => 'https://github.com/taylorotwell/bugsnag-php');
	}

}