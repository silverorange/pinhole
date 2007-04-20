<?php

require_once 'XML/RPC2/Server.php';

/**
 * @copyright 2007 silverorange
 */
class UploaderStatusServer
{
	/**
	 * @xmlrpc.hidden()
	 */
	public function __construct()
	{
		if (isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
			$server = XML_RPC2_Server::create($this);
			$this->setHeaders();
			$server->handleCall();
		} else {
			echo 'No HTTP POST data found.', "\n";
		}
	}

	/** 
	 * Gets upload status for the given upload identifiers
	 *
	 * Statuses for the given identifiers are returned in an array as follows:
	 *
	 * If the uploadprogress extension is loaded and a file upload is in
	 * progress for the given ids:
	 *
	 *   <code>{ sequence: sequence_number, status: status_struct }</code>
	 *
	 * Otherwise:
	 *
	 *   <code>{ sequence: sequence_number, status: 'none' }</code>
	 *
	 * @param array $ids an array of strings containing upload identifiers.
	 * @param ineger $sequence the sequence id of this request to prevent race
	 *                          conditions.
	 *
	 * @return array an array of structs containing upload status information.
	 */
	public function getStatus(array $ids, $sequence)
	{
		$return = array();

		foreach ($ids as $id) {
			$response = array();
			$response['sequence'] = $sequence;

			if (function_exists('uploadprogress_get_info') &&
				$uploadprogress_status = uploadprogress_get_info($id)) {
				$status = array();
				foreach ($uploadprogress_status as $key => $value)
					$status[$key] = $value;

				$response['status'] = $status;
			} else {
				$response['status'] = 'none';
			}

			$return[] = $response;
		}

		return $return;
	}

	/**
	 * @xmlrpc.hidden
	 */
	protected function setHeaders()
	{
		// Set content type to XML
		header('Content-type: text/xml; charset=UTF-8');

		// Disable any caching with HTTP headers
		// Any date in the past will do here
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

		// Set always modified
		// for HTTP/1.1
		header('Cache-Control: no-cache, must-revalidate max-age=0');
		// for HTTP/1.0
		header('Pragma: no-cache');
	}
}

$server = new UploaderStatusServer();

?>
