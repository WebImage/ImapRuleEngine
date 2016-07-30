<?php 

namespace WebImage\ImapProcessor;

class StatusCache {
	
	private $data=array();
	
	public function get($key, $default=null) {
		if ($this->has($key)) return $this->data[$key];
		else return $default;
	}
	
	public function set($key, $val) {
		$this->data[$key] = $val;
	}
	
	public function has($key) {
		return (isset($this->data[$key]));
	}
	
	public function del($key) {
		if (isset($this->data[$key])) unset($this->data[$key]);
	}
	public function getKeys() { return array_keys($this->data); }
}
