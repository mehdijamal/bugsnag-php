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
	 * @param  array  $metaData
	 * @return void
	 */
	public function notify($exception, array $metaData = array())
	{
		$payload = $this->buildPayload($exception, $metaData);

		$headers = array('Content-Type' => 'application/json');

		$request = $this->http->post($this->getApiUrl(), $headers, $payload);

		try {
			$request->send();
		} catch (\Guzzle\Http\Exception\BadResponseException $guzzleException) {
			$this->handleBadResponse($guzzleException, $guzzleException->getResponse());
		}
	}

	/**
	 * Build the Bugsnag payload for an exception.
	 *
	 * @param  Exception|array  $e
	 * @return string
	 */
	protected function buildPayload($exceptions, array $metaData)
	{
		if ( ! is_array($exceptions)) {
			$exceptions = array($exceptions);
		}

		return json_encode(array(
			'apiKey'   => $this->apiKey,
			'notifier' => $this->getNotifierInfo(),
			'events'   => array($this->buildEventPayloadArray($exceptions)),
			'metaData' => $metaData
		));
	}

	/**
	 * Build the payload for an event.
	 *
	 * @param  array  $exceptions
	 * @return array
	 */
	protected function buildEventPayloadArray(array $exceptions)
	{
		$event = $this->baseData;

		foreach ($exceptions as $exception) {
			$event['exceptions'][] = $this->buildExceptionArray($exception);
		}

		return $event;
	}

	/**
	 * Build the payload array for a specific exception instance.
	 *
	 * @param  Exception  $e
	 * @return array
	 */
	protected function buildExceptionArray(\Exception $e)
	{
		$payload = array('errorClass' => get_class($e), 'message' => $e->getMessage());

		// If no trace is available, we will just assign "n/a" to the file and method
		// and assign a dummy line number. This should not generally happen, and
		// would only occur if an exception was thrown from the root script.
		if (count($e->getTrace()) == 0) {
			$payload['stacktrace'][] = array(
				'file' => 'n/a', 'lineNumber' => 1, 'method' => 'n/a',
			);
		}

		foreach ($e->getTrace() as $trace) {
			$traceDetail = array();
			if (isset($trace['file'])) {
				$traceDetail['file'] = $trace['file'];
			} else {
				$traceDetail['file'] = 'n/a';
			}
			if (isset($trace['line'])) {
				$traceDetail['lineNumber'] = $trace['line'];
			} else {
				$traceDetail['lineNumber'] = 1;
			}
			if (isset($trace['function'])) {
				$traceDetail['method'] = $trace['function'];
			} else {
				$traceDetail['method'] = 'n/a';
			}

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