<?php 

namespace WebImage\RuleEngine;

class AnonymousRule extends AbstractRule {
	
	private $evaluate = null;
	private $execute = null;
	
	public function __construct($eval_func=null, $exec_func=null)
	{
		$this->evaluate = $eval_func;
		$this->execute = $exec_func;
	}
	
	public function matches(Context $ctx) {
		if (is_callable($this->evaluate)) {
			$response = call_user_func($this->evaluate, $ctx);
			return (true === $response);
		} else {
			return false;
		}
	}
	public function execute(Context $ctx) {
		if (is_callable($this->execute)) call_user_func($this->execute, $ctx);
	}
}