<?php namespace Bugsnag\Notifier;

class JsClient extends Client {

	/**1
	 * Build the payload for an event.
	 *
	 * @param  array  $exceptions
	 * @return array
	 */
	protected function buildEventPayloadArray(array $exceptions, array $metaData)
	{
		$event = $this->baseData;

		$event['exceptions'][] = $this->buildExceptionArray($exceptions);

		$event['metaData'] = $metaData;

		return $event;
	}


	/**
	 * Build the payload array for a specific exception instance.
	 *
	 * @param  array  $e
	 * @return array
	 */
	protected function buildExceptionArray(array $e)
	{
		$payload = array('errorClass' => $e['type'], 'message' => $e['message']);

		$payload['stacktrace'][] = array(
			'file' => $e['file'], 'lineNumber' => $e['line'], 'method' => 'n/a',
		);

		return $payload;
	}
}
