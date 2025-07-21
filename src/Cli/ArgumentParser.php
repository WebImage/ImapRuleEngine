<?php

namespace WebImage\Cli;
// Command-line arguments
class ArgumentParser {
	private $command;
	private $flags = array();
	
	/**
	 * @param An array of arguments, usually passed as $argv
	 * @param An option array of known flags that can be set - any flags set that are not explicitly set throw an error
	 * @throws \InvalidArgumentException
	 * @example $args = new ArgumentParser($argv, array('a', 'goodflag'));
	 **/
	public function __construct(array $raw_command_args, array $known_flags=null) {
		$this->command = $raw_command_args[0];

		$flag_name = 'global';
		for($i=1; $i < count($raw_command_args); $i++) {
			$arg = $raw_command_args[$i];
			if (substr($arg, 0, 1) == '-') {
				$remove_char = 1;
				if (substr($arg, 1, 1) == '-') $remove_char ++;
				
				$flag_name = substr($arg, $remove_char);
				$arg = true; // Set default value
			}
			$this->flags[$flag_name] = $arg;
		}
		
		if (null !== $known_flags) $this->knownFlags($known_flags);
	}
	public function getCommand() { return $this->command; }
	public function isFlagSet($flag) {
		return isset($this->flags[$flag]);
	}
	public function getFlag($flag, $default=null) {
		if ($this->isFlagSet($flag)) return $this->flags[$flag];
		else return $default;
	}
	public function setFlag($name, $value) {
		$this->flags[$name] = $value;
	}
	
	/**
	 * Check to see if any specified arguments are not within a known set of flags
	 * @throw \InvalidArgumentException
	 **/
	protected function knownFlags(array $flags) {
		$unknown_flags = array_diff(array_keys($this->flags) , $flags);
		if (count($unknown_flags) > 0) throw new \InvalidArgumentException(sprintf('Unknown flags: %s', implode(', ', $unknown_flags))); 
		return true;
	}
}