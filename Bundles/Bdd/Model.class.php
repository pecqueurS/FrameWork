<?php


namespace Bundles\Bdd;

use Bundles\Bdd\Db;


class Model {

	protected $db;
	protected $table=array();
	protected $modifiedRows=array();

	protected $tableName = '';


	public function __construct($db = null) {
		if ($db === null) {
			$this->db = Db::init($this->tableName);
		} else {
			if ($this->tableName == '') {
				$this->tableName = $db->getDefaultTable();
			}
			$this->db = $db;
			$this->loadTable();
		}
	}


	public static function init($db = null) {
		$class = get_called_class();
		return new $class($db);
	}


	public static function getNewEntity() {
		$class = get_called_class();
		$model = new $class();
		$fields = $model->db->getFields();
		$result = array();
		foreach ($fields as $field) {
			$result[$field['Field']] = null;
		}

		return $result;
	}

	public function loadTable() {
		$this->table = $this->db->select();
	}


	public function getField($field) {
		return $this->table[$field];
	}

	public function getFieldsName() {
		return array_keys($this->table);
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

	public function setValues($values) {
		if (!empty($values)) {
			$this->modifiedRows[] = $values;
		}

		return $this;
	}

	public function save() {
		$affected_rows = 0;
		foreach ($this->modifiedRows as $row) {
			$fields = array_keys($row);
			if (!empty($fields)) {
				if ($row[$fields[0]] === null) {
					$affected_rows += $this->db->insert(array_values($row));
				} else {
					$primary = array_shift($row);
					$this->db->addRule($fields[0], $primary);
					
					$fields = array_keys($row);
					$values = array_values($row);
					$affected_rows += $this->db->update($values, $fields);
				}
			}
		}

		return $affected_rows >= 0;
	}

	public function delete() {
		$affected_rows = 0;
		foreach ($this->modifiedRows as $row) {
			$fields = array_keys($row);
			if (!empty($fields)) {
				$primary = array_shift($row);
				$this->db->addRule($fields[0], $primary);
				$affected_rows += $this->db->delete();
			}
		}

		return $affected_rows >= 0;
	}



}


?>