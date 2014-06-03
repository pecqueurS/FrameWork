<?php


namespace WOTG\Models;

use Bundles\Bdd\Db;
use Bundles\Bdd\Model;


class DescPagesModel extends Model {

	protected $tableName = "desc_pages";
	
	public function __construct() {
		parent::__construct();
		$this->getDescriptions();
	}

	public static function init() {
		$mod = new DescPagesModel();
		return $mod;
	}



	protected function getDescriptions(){
		$result = $this->db->addJoin("pages", array("pag_id", "dep_pages_id"))
						   ->addJoin("elem_a_trad", array("eat_id", "dep_elem_a_trad_id"))
						   ->addJoin("traductions", array("eat_id", "tra_elem_a_trad_id"))
						   ->addJoin("langues", array("lan_id", "tra_langues_id"))
						   ->addRule("lan_designation", $_SESSION["lang"])
						   ->select(array("tra_nom","pag_name"));

		$this->table = $result;


	}




}


?>