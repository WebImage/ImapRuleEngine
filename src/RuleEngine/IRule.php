<?php

namespace WebImage\RuleEngine;

interface IRule {
    /**
     * Evaluate context and determine if this rule matches
     *
     * @param WebImage\RuleEngine\Context
     * @return bool Whether or not the rule matches
     */
	public function matches(Context $context);
    /**
     * Execute the rule
     *
     * @return void
     */
	public function execute(Context $context);
}