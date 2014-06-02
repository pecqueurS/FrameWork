<?php

namespace Bundles\Bdd;

use Bundles\Parametres\Conf;


class Db extends BDD {

	protected $cnx;

	protected $tables = array();

	protected $fields = array();

	protected $construct_request = "";

	protected $joins = array();
	protected $rules = array();
	protected $orders = array();
	protected $groups = array();

	protected $values = array();


	public function __construct($table=null) {
		parent::__construct();
		$this->cnx = new BDD();
		if($table !== NULL) {
			$this->addTable($table);
		}
	}

	// Initialisation en static pour raccourcir le code
	public static function init($table=null) {
		$db = new Db($table);
		return $db;
	}

	// Ajout d'une table
	public function addTable($table){
		$this->tables[] = $table;
		$this->searchFields($table);
		return $this;
	}

	// Recherche les colonnes d'une table
	protected function searchFields($table) {
		$sql = "SHOW COLUMNS FROM ".$table;
		$this->requete($sql);
		$this->fields[$table] = $this->retourne_tableau();
	}

	// Ajoute une jointure
	public function addJoin($table, $keys, $type="INNER") {
		$this->joins[] = array(
			"table"=>$table,
			"keys"=>$keys,
			"type"=>$type
		);
		$this->searchFields($table);
		return $this;
	}

	// Ajoute une condition
	public function addRule($field, $value, $type="=") {
		$this->rules[] = array(
			"field"=>$field,
			"value"=>$value,
			"type"=>$type
		);
		return $this;
	}

	// Ajoute une condition complexe 
	public function addComplexRule($rule) {
		$this->rules = $rule;
		return $this;
	}

	// Ajoute un tri
	public function addOrder($field, $order="ASC") {
		$this->orders[] = array(
			"field"=>$field,
			"order"=>$order
		);
		return $this;
	}

	// Ajoute un "group by"
	public function addGroup($field, $complex="") { // ex: $complex = HAVING prix_moyen <= 10
		$this->groups[] = array("field"=>$field, "complex"=>$complex);
		return $this;
	}

	// Contruit la requete
	protected function construct_request($type, $attr=array()) {
		// Base de la requete
		switch ($type) {
			case 'select':
				if($attr['fields'][0]=="*"){
					$fields = "";
					foreach ($this->fields as $table_fields) {
						foreach ($table_fields as $field) {
							$fields .= $field["Field"].",";
						}
					}
					$fields = substr($fields,0,-1);
				} else {
					$fields = implode(",",$attr['fields']);
				}

				$tables = "";
				for ($i=0; $i < count($this->tables); $i++) { 
					$tables .= (($i==0) ? "" : ", " ).$this->tables[$i];
				}

				$this->construct_request = "SELECT $fields FROM $tables ";

				break;
			
			case 'insert':

				$fields = "(".implode(",",$attr['fields']).")";

				if(is_array($attr['values'][0])) {
					$values = "";
					for ($i=0; $i < count($attr['values']) ; $i++) { 
						$val = $this->stmtPrepare($attr['fields'],$attr['values'][$i]);
						$values .= (($i==0) ? "" : ", " )."(".implode(",",$val).")";
					}
				} else {
					$val = $this->stmtPrepare($attr['fields'],$attr['values']);
					$values = "(".implode(",",$val).")";
				}


				$this->construct_request .= "INSERT INTO {$this->tables[0]} $fields VALUES $values ";

				break;
			
			case 'update':
				$values = $this->stmtPrepare($attr['fields'],$attr['values']);
				if(is_array($attr['fields'])) {
					$updates ="";
					for ($i=0; $i < count($attr['fields']) ; $i++) { 
						$updates .= (($i==0) ? "" : ", " )."{$attr['fields'][$i]} = {$values[$i]} ";
					}
				} else {
					$updates = $attr['fields']." = ".$values[0];
				}

				$this->construct_request .= "UPDATE {$this->tables[0]} SET $updates ";

				break;
			
			case 'delete':
				$this->construct_request .= "DELETE FROM {$this->tables[0]} ";

				break;
			
		}

		// AJOUT DES JOINTURES
		foreach ($this->joins as $join) {
			$this->construct_request .= "{$join['type']} JOIN {$join['table']} ON ({$join['keys'][0]} = {$join['keys'][1]}) ";
		}

		// AJOUT DES CONDITIONS
		if(is_array($this->rules)) {
			for ($i=0; $i < count($this->rules) ; $i++) { 
				$this->construct_request .= (($i==0) ? "WHERE " : "AND ").$this->rules[$i]['field']." " ;

				$values = $this->stmtPrepare($this->rules[$i]['field'],$this->rules[$i]['value']);

				switch ($this->rules[$i]['type']) {
					case '=':
					case '<>':
					case '!=':
					case '<':
					case '>':
					case '<=':
					case '>=':
					case 'IS':
					case 'IS NOT':
					case 'LIKE':
					case 'NOT LIKE':
						$this->construct_request .= $this->rules[$i]['type']." ".$values[0]." ";
						break;
					
					case 'BETWEEN':
					case 'NOT BETWEEN':
						$this->construct_request .= $this->rules[$i]['type']." ".$values[0]." AND ".$values[1]." ";
						break;
					
					case 'IN':
					case 'NOT IN':
						$this->construct_request .= $this->rules[$i]['type']." (";
						$this->construct_request .= implode(",", $values);
						$this->construct_request .= ") ";
						break;
				}
				
			}
		} else {
			$this->construct_request .= $this->rules." ";
		}

		// AJOUT GROUP BY
		for ($i=0; $i < count($this->groups); $i++) { 
			if($i==0) {
				$this->construct_request .= "GROUP BY ";
			} else {
				", ";
			}
			$this->construct_request .= $this->groups[$i]["field"]." ".$this->groups[$i]["complex"]." ";
		}

		// AJOUT ORDER BY
		for ($i=0; $i < count($this->orders); $i++) { 
			if($i==0) {
				$this->construct_request .= "ORDER BY ";
			} else {
				", ";
			}
			$this->construct_request .= $this->orders[$i]["field"]." ".$this->orders[$i]["order"]." ";
		}

		
		return $this->construct_request;

		
	}

	// Renvoi la requete
	public function getRequest() {
		return $this->construct_request;
	}

	// Renvoi les champs des colonnes sous forme d'un simple tableau
	public function getFields() {
		foreach ($this->fields as $table) {
			foreach ($table as $field) {
				$result[] = $field;
			}
		}
		return $result;
	}

	// Fait un SELECT
	public function select($fields=array("*")) {
		$sql = $this->construct_request("select",array("fields"=>$fields));
		return $this->exec($sql);
	}

	// Fait un INSERT INTO
	public function insert($values, $fields=false) {
		if($fields===false) {
			$fields = array();
			foreach ($this->fields[$this->tables[0]]["Field"] as $field) {
				$fields[] = $field;
			}
		}
		$sql = $this->construct_request("insert",array("fields"=>$fields, "values"=>$values));
		return $this->exec($sql);
	}

	public function update($values, $fields) {
		$sql = $this->construct_request("update",array("fields"=>$fields, "values"=>$values));
		return $this->exec($sql);
	}

	public function delete() {
		$sql = $this->construct_request("delete");
		return $this->exec($sql);
	}

	protected function setBind($bind){
		$this->bind = $bind;
		return $this;
	}

	protected function setValues($values){
		$this->values = $values;
		return $this;
	}

	public function perso($sql) {
		$this->construct_request = $sql;
		return $this->exec($sql);
	}

	


	protected function stmtPrepare($fields, $values) {
		if(!is_array($fields)) {
			$fields = array($fields);
		}
		if(!is_array($values)) {
			$values = array($values);
		}

		// créé un tableau type
		foreach ($this->fields as $table) {
			foreach ($table as $field) {
				$t=explode("(",$field['Type']);
				$type[$field['Field']] = trim($t[0]);
			}
		}

		$varToTest = array();
		// WHERE
		if(count($fields==1)) {

			$bind = $this->addBind($fields[0],$type);
			
			foreach ($values as $value) {
				$varToTest[]= " ? ";
				$this->values[] = $value;
				$this->bind .= $bind;
			}
		}

		// AUTRES
		if(count($fields)>1) {

			$i=0;
			foreach ($values as $value) {
				// INSERT, UPDATE, 
				if(!is_array($value)) {

					$varToTest[]= " ? ";
					$this->values[] = $value;
					$this->bind .= $this->addBind($fields[$i],$type);
										
				} else {
					// MULTIPLE INSERTS
					foreach ($value as $val) {

						$varToTest[]= " ? ";
						$this->values[] = $val;
						$this->bind .= $this->addBind($fields[$i],$type);

					}
				}
				$i++;

			}
		}

		return $varToTest;

	}



	// Statements

	protected function addBind($field,$type){
		switch (strtoupper($type[$field])) {
			case 'INT':
			case 'TINYINT':
			case 'SMALLINT':
			case 'MEDIUMINT':
			case 'INTEGER':
			case 'BIGINT':
			case 'BOOL':
				return "i";
				break;
			
			case 'FLOAT':
			case 'DOUBLE':
			case 'REAL':
			case 'DECIMAL':
			case 'NUMERIC':
				return "d";
				break;

			case 'BLOB':
				return "b";
				break;

			default:
				return "s";
				break;
		}
	}

	protected function exec($sql){
		if($this->bind != "") {
			$this->prepare($sql,$this->bind);
			return $this->execute($this->values);
		} else {
			$this->prepare($sql);
			return $this->execute();
		}
	}















}




//$players = Db::init("joueurs")->addRule('jou_id',21)->select();


//var_dump($players);








?>