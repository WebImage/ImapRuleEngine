<?php

namespace WebImage\RuleEngine;

abstract class AbstractRule implements IRule {
	public function matches(Context $context) { return false; }
	public function execute(Context $context) {}
}