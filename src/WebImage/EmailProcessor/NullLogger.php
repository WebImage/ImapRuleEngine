<?php

namespace WebImage\EmailProcessor;

class NullLogger implements ILogger {
	public function log($log) {}
}