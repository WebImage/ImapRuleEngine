<?php

namespace WebImage\ImapProcessor;

class NullLogger implements ILogger {
	public function log($log) {}
}