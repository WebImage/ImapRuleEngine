<?php 

namespace WebImage\ImapProcessor;

class Person {
	
	private $label, $name, $domain;
	
	public function __construct($label, $name, $domain) {
		$this->label = $label;
		$this->name = $name;
		$this->domain = $domain;
	}
	public function getLabel() { return $this->label; }
	public function getName() { return $this->name; }
	public function getDomain() { return $this->domain; }
	
	public static function createFromString($str) {
		
		$email = trim($str);
		$label = $email;
		
		if (preg_match('/(.+)<(.+)>/', $str, $match)) {
				$label = trim($match[1]);
				$email = $match[2];
		}
		
		$name = '';
		$parts = explode('@', strtolower($email));
		if (count($parts) > 1) list($name, $domain) = $parts;
		else $domain = $parts[0];
		
		return new Person($label, $name, $domain);
		
	}
	
	public function getEmail() { return sprintf('%s@%s', $this->getName(), $this->getDomain()); }
	public function __toString() {
		return $this->getEmail();
	}
}