<?php
use WebImage\RuleEngine\AnonymousRule;
use WebImage\RuleEngine\Context;

return array(
	'Name of Rule' => new AnonymousRule(
        // Evaluate context to determ if message matches rule
		function(Context $ctx) {
            /**
             * @var WebImage/EmailProcessor/Message The message being operated on
             **/
			if ($msg = $ctx->get('message')) {
				// Conditions to evaluate.  If message matches this rule return true
			}
			return false;
		},
		function(Context $ctx) {
            /**
             * @var WebImage/EmailProcessor/Message The message being operated on
             **/
			if ($msg = $ctx->get('message')) {
				// Actions to perform on message
			}
		}
	)
);