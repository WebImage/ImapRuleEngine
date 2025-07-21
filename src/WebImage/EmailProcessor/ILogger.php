<?php 

namespace WebImage\EmailProcessor;

interface ILogger {
	/**
	 * Log a message somewhere somehow
	 *
	 * @param string $log The entry to be logged
	 **/
	public function log($log);
}