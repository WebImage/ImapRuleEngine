#!/usr/local/env php
<?php

use WebImage\ImapProcessor\Mbox;
use WebImage\ImapProcessor\StatusCache;
use WebImage\ImapProcessor\MessageEvent;
use WebImage\ImapProcessor\Message;
use WebImage\ImapProcessor\NullLogger;

use WebImage\RuleEngine\AnonymousRule;
use WebImage\RuleEngine\Context;
use WebImage\Cli\ArgumentParser;

define('DIR_INC', __DIR__ . '/inc/');
define('CACHE_KEY_LAST_UID', 'Last UID');
define('FOLDER_TRASH', 'INBOX.PHP');

require_once(__DIR__ . DIRECTORY_SEPARATOR . 'autoload.php');

/**
 * Passed arguments
 **/
$args = new ArgumentParser($argv, array('c', 'cache', 'd', 'l'));
$config_file = $args->getFlag('c');
$cache_dir = $args->getFlag('cache');
$is_debug_mode = $args->isFlagSet('d');
$should_loop = $args->isFlagSet('l');


/**
 * Make sure config settings exist
 */
if (null === $config_file) die('Must provider config file -c [file]' . PHP_EOL);
else if (!file_exists($config_file)) die('Missing configuration file: ' . $config_file . PHP_EOL);
else if (!is_array($config = require($config_file))) die('Config did not return array' . PHP_EOL);


/**
 * Setup cache
 */
if (null === $cache_dir) {
	$cache_dir = __DIR__ . '/cache';
	@mkdir($cache_dir);
	if (!file_exists($cache_dir)) die('Specify cache directory with --cache [path]' . PHP_EOL);
}
$cache_dir = rtrim($cache_dir, '/\\');
if (!file_exists($cache_dir) || !is_writable($cache_dir)) die('Cache directory must be writable: ' . $cache_dir . PHP_EOL);

/**
 * Check if script is already runnning
 */
if (!$is_debug_mode) {
	
	$pid = getmypid();
	$pid_file = $cache_dir . '/' . basename($argv[0]) . '.pid';
	
	if (file_exists($pid_file)) {
		$previous_pid = file_get_contents($pid_file);
		die('This script is still running or may have crashed.  The previous PID was ' . $previous_pid . '.' . PHP_EOL . 'If the program crashed then remove ' . $pid_file . PHP_EOL);
	}
	
}

/**
 * Get basic settings from configuration
 **/
$server = get_param($config, 'server');
$email = get_param($config, 'email');
$pass = get_param($config, 'pass');
$rule_files = get_param($config, 'rules');

if (empty($server)) die('Config missing server' . PHP_EOL);
else if (empty($email)) die('Config missing email' . PHP_EOL);
else if (empty($pass)) die('Config missing password' . PHP_EOL);
else if (empty($rule_files)) die('Config missing rules' . PHP_EOL);


/**
 * Create cache file based on email address
 */
$key = preg_replace('/[^a-zA-Z0-9]+/', '_', $email);
$cache_file = rtrim($cache_dir, '/\\') . '/' . sprintf('%s.cache', $key);


/**
 * Setup cache, logger, and mailbox objects
 * @var StatusCache $cache
 * @var Logger $logger
 * @var Mbox $mbox
 */
$cache = file_exists($cache_file) ? unserialize(file_get_contents($cache_file)) : new StatusCache();
$logger = new NullLogger();
$mbox = new Mbox($server, 'INBOX', $email, $pass, $logger);


/**
 * Gather rules
 */
$rules = array();

foreach($rule_files as $file) {
	$file_rules = require($file);
	if (!is_array($file_rules)) die(sprintf("%s did not return an array of rules\n", $file));

	$rules = array_merge($rules, $file_rules);
}


/**
 * Get the last message id processed so that we can continue from there
 */
$last_uid = $cache->get(CACHE_KEY_LAST_UID);

/**
 * Main program loop
 **/

do {

	// Mark this process as being in the middle of running
	if (!$is_debug_mode) {
		file_put_contents($pid_file, $pid);
		if (!file_exists($pid_file)) {
			echo 'Unable to create PID file: ' . $pid_file . PHP_EOL;
			break;
		}
	}
	
	$messages = null;
	
	if (null === $last_uid) {
		$last_uid = 0;
		$messages = $mbox->getMessages();
	} else {
		$messages = $mbox->getMessages($last_uid+1, '*', FT_UID);
	}
	
	foreach($messages as $msg) {
		if ($msg->getUid() > $last_uid) {
			$last_uid = $msg->getUid();
		}
	}
	
	if ($last_uid > 0) {
		#$cache->set(CACHE_KEY_LAST_UID, $last_uid);
	}
	
	$n_messages = count($messages);
	
	if ($n_messages > 0) {
		
		echo number_format($n_messages) . ' new message(s) added at ' . date('Y-m-d H:i:s') . PHP_EOL;

		foreach($messages as $msg) {
			
			if ($msg->getStatus() != Message::STATUS_NORMAL) continue;
			
			$from = $msg->getFrom();
			$to = $msg->getTo();
			
			$ctx = new Context(array(
				'message' => $msg
			));
			
			$matched_rules = array();
			
			foreach($rules as $rule_name => $rule) {
				
				if ($rule->matches($ctx)) {
					$matched_rules[] = $rule_name;
					
					if (!$is_debug_mode) $rule->execute($ctx);
				}
				
			}
			$x = count($matched_rules) > 0 ? 'x' : ' ';
			
			echo '[' . $x . '] #' . $msg->getUid() . ' ' . date('Y-m-d H:i:s', $msg->getDate()) . ' <' . $msg->getFrom() . '> ' . ' <' . $msg->getTo() . '> ' . $msg->getSubject();
			if (count($matched_rules) > 0) echo ' [' . implode(', ', $matched_rules) . ']';
			echo PHP_EOL;
		}
		echo PHP_EOL;	
	}
	
	// Update cache
	file_put_contents($cache_file, serialize($cache));
	
	
	if ($is_debug_mode) {
		
		break;
		
	} else {
		
		// Mark this process as being safe to CTRL+C out of
		unlink($pid_file);
		
	}
	if ($should_loop) sleep(15);
	
} while ($should_loop);

function get_param(array $params, $key, $default=null) {
	if (isset($params[$key])) return $params[$key];
	else return $default;
}
