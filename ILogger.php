<?php

namespace Ciki\Deploy;

interface ILogger
{

	/**
	 * Write $message to the log file.
	 * @param string $message 	The message to write
	 * @param string $priority 	The message priority (e.g. INFO, DEBUG, ERROR, etc.)
	 * @param bool $print		Print the log message?
	 */
	public function log($message, $priority = 'INFO', $print = false);
}