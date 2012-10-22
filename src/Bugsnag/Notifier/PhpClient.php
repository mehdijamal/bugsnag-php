<?php namespace Bugsnag\Notifier;

class PhpClient extends Client {

	/**
	 * Build the payload for an event.
	 *
	 * @param  array  $exceptions
	 * @return array
	 */
	protected function buildEventPayloadArray(array $exceptions, array $metaData)
	{
		$event = $this->baseData;

		foreach ($exceptions as $exception) {
			$event['exceptions'][] = $this->buildExceptionArray($exception);
		}

		$event['metaData'] = $metaData;

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
}