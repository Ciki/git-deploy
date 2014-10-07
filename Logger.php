<?php

namespace Ciki\Deploy;

class Logger implements ILogger
{
	/** @var string name of the directory where errors should be logged; FALSE means that logging is disabled */
	private $directory;

	/**
	 * @var string Timestamp format used for logging.
	 * @link http://www.php.net/manual/en/function.date.php
	 */
	private $dateFormat;
	private $defaultDateFormat = 'Y-m-d H:i:sP';

	/** @var bool $print Print the log message? */
	private $flushMessages = false;


	/**
	 * @param string
	 * @param string
	 * @param bool
	 */
	public function __construct($directory, $dateFormat = NULL, $flushMessages = false)
	{
		$this->set($directory, $dateFormat, $flushMessages);
	}


	/**
	 * @param string
	 * @param string
	 * @param bool
	 */
	public function set($directory, $dateFormat = NULL, $flushMessages = false)
	{
		$this->directory = $directory;
		$this->dateFormat = $dateFormat === NULL ? $this->defaultDateFormat : $dateFormat;
		$this->flushMessages = (bool) $flushMessages;
	}


	/**
	 * Write $message to the log file.
	 * @param string $message 	The message to write
	 * @param string $priority 	The message priority (e.g. INFO, DEBUG, ERROR, etc.)
	 */
	public function log($message, $priority = 'INFO', $print = false)
	{
		if (!is_dir($this->directory)) {
			throw new \RuntimeException("Directory '$this->directory' is not found or is not a directory.");
		}

		if (!is_string($message)) {
			$message = print_r($message, true);
		}
		$filename = $this->directory . '/' . strtolower($priority) . '.log';
		$message = date($this->dateFormat) . " --- $message";
		if (!@file_put_contents($filename, $message . PHP_EOL, FILE_APPEND | LOCK_EX)) {
			throw new \RuntimeException("Unable to write to log file '$filename'. Is directory writable?");
		}

		if ($print || $this->flushMessages) {
			echo $message;
		}
	}


}