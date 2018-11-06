<?php 

namespace WebImage\EmailProcessor;

class FileLogger implements ILogger {
	private $fp;
	function __construct($file) {
		$this->fp = fopen($file, 'a+');
		if (!is_resource($this->fp)) die('Invalid ' . $file);
	}
	public function log($message) {
		fwrite($this->fp, date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL);
	}
	function __destruct() {
		if ($this->fp) fclose($this->fp);
	}
}