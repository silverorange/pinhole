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
	 * @param array an array of strings containing upload identifiers.
	 *
	 * @return array an array of structs containing upload status information.
	 */
	public function getStatus(array $ids)
	{
		$return = array();

		foreach ($ids as $id) {
			if (function_exists('uploadprogress_get_info')) {
				$status = uploadprogress_get_info($id);

				if ($status === null) {
					$obj = false;
				} else {
					$obj = array();
					foreach ($status as $key => $value)
						$obj[$key] = $value;
				}
			} else {
				$obj = true;
			}

			$return[] = $obj;
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
