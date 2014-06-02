<?php


namespace Bundles\Translate\bdd;

use Bundles\Bdd\Db;
use Bundles\Bdd\Model;

class DictionnaireModel extends Model {

	protected $tableName = "dictionnaire";
	
	public function __construct() {
		parent::__construct();
		$this->getDico();
	}

	public static function init() {
		$mod = new DictionnaireModel();
		return $mod;
	}



	protected function getDico(){
		$result = $this->db->addJoin("langues", array("lan_id", "dic_langues_id"))
								->addRule("lan_designation", $_SESSION["lang"])
								->select(array("dic_designation","dic_traduction"));

		$this->table = array();
		foreach ($result as $dico) {
		  $this->table[$dico["dic_designation"]] = $dico["dic_traduction"];
		}

	}




}


?>