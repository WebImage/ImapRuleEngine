<?php 

namespace WebImage\EmailProcessor;

class MessageEvent {
	private $ruleName; 
	#private $mbox;
	private $message;
	private $isCancelled = false;
	public function __construct($rule_name, Message $message) {
		$this->ruleName = $rule_name;
		#$this->mbox = $mbox;
		$this->message = $message;
	}
	public function getRuleName() { return $this->ruleName; }
	#public function getMbox() { return $this->mbox; }
	public function getMessage() { return $this->message; }
	public function isCancelled() { return $this->isCancelled; }
	public function cancel() { $this->isCancelled = true; }
}