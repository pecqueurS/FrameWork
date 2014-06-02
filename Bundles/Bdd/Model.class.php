<?php


namespace Bundles\Bdd;

//use Bundles\Bdd\Db;


class Model {

	protected $db;
	protected $className="";
	protected $table=array();

	public function __construct() {
		$this->db = Db::init($this->tableName);
		$this->defineClass();
		//$this->loadTable();	
	}


	public static function init() {
		$mod = new Model();
		return $mod;
	}


	protected function defineClass() {
		$table = explode("_",$this->tableName);
		for ($i=0; $i < count($table); $i++) { 
			$table[$i] = ucfirst($this->tableName);
		}
		$this->className = implode("", $table)."Model";
	}


	protected function loadTable() {
		$this->table = $this->db->select();
	}


	public function getField($field) {
		return $this->table[$field];
	}

	public function getValues($fields=array(), $rules=array()) {
		$result = array();
		$fields = (!is_array($fields)) ? array($fields) : $fields ;
		if(empty($fields)) return $this->table;
		$i = 0;
		foreach ($this->table as $row) {
			$takeRow = true;
			foreach ($rules as $key => $value) {
				if(is_array($value)) {
					if(!in_array($row[$key], $value)) $takeRow = false;
					
				} else {
					if($row[$key]!=$value) $takeRow = false;
				}
				
			}
			if($takeRow) {
				foreach ($fields as $field) {
					$result[$i][$field] = $row[$field];
				}
				$i++;
			}
		}

		if(count($result)==1) $result = $result[0];

		return $result;
	}



}


?>