<?php

namespace WebImage\RuleEngine;

class Context {
	private $data;
	
	public function __construct(array $init_vals=array()) {
		foreach($init_vals as $key=>$val) {
			$this->set($key, $val);
		}
	}
	
	public function get($key, $default=null) {
		if ($this->has($key)) return $this->data[$key];
		else return $default;
	}
	public function has($key) {
		return (isset($this->data[$key]));
	}
	public function set($key, $val) {
		$this->data[$key] = $val;
		return $this;
	}
	public function del($key) {
		if ($this->has($key)) unset($this->data[$key]);
	}
}