<?php 

namespace WebImage\ImapProcessor;

interface ILogger {
	/**
	 * Log a message somewhere somehow
	 *
	 * @param string $log The entry to be logged
	 **/
	public function log($log);
}