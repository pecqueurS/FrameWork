<?php


namespace Bundles\Parametres;

class ConfEntity {

	private $conf = array();

	public function __construct($conf = array()) {
		$this->addConf($conf);
	}

	public function getValue($key) {
		return $this->conf[$key];
	}

	public function setValue($key, $value) {
		$this->conf[$key] = $value;
		return $this->conf[$key];
		
	}

	public function getConf() {
		return $this->conf;
	}

	public function addConf($conf) {
		$this->conf = array_merge($this->conf, $conf);
	}

	public function __call($method, $arguments) {
		$field = lcfirst(substr($method,3)); 
		
		foreach ($this->conf as $key => $conf) {
			if ($key == $field) {
				return $conf;
			}

			if (is_array($conf)) {
				if (isset($conf[$field])) {
					return $conf[$field];
				}
			} 
		}

		throw new Exception('vous avez appelez la methode $method mais elle ne semble pas exister.');
		
		return false;
	}
}

?>