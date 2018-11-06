<?php 

namespace WebImage\EmailProcessor;

class EchoLogger implements ILogger {
	public function log($log) {
		echo $log . PHP_EOL;
	}
}