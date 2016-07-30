# Mail rules
A simple IMAP mail engine designed to mark and move messages around based on a set of rules.

## Usage

php mailrules.php -c [path-to-mailbox-config.php]

### Example config file

```php
<?php
// config/johndoe_domain_com.php
return array(
    // The IMAP server
	'server' => 'server.com',
    // The email | username
	'email' => 'name@domain.com',
    // Password for mailbox
	'pass' => 'secret',
    // An array of paths to rules files
	'rules' => glob('/path/to/rule_files/*')
);
```

Each rule file in 'rules' returns an associative array, where the key is a friendly name for the rule, and the value is the rule handler.  As the program iterates through each message in the mailbox the each rules checks to see whether the message fits the rule criteria.  If so, the rule executes instructions on the message.

### Example rule file

```php
<?php
use WebImage\RuleEngine\AnonymousRule;
use WebImage\RuleEngine\Context;

return array(
	'Name of Rule' => new AnonymousRule(
        // Evaluate context to determine if message matches rule
		function(Context $ctx) {
            /**
             * @var WebImage/ImapProcessor/Message The message being operated on
             **/
			if ($msg = $ctx->get('message')) {
				// Conditions to evaluate.  If message matches this rule return true
			}
			return false;
		},
		function(Context $ctx) {
            /**
             * @var WebImage/ImapProcessor/Message The message being operated on
             **/
			if ($msg = $ctx->get('message')) {
				// Actions to perform on message
			}
		}
	)
);
```