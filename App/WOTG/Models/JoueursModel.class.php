<?php


namespace WOTG\Models;

use Bundles\Bdd\Db;
use Bundles\Bdd\Model;


class JoueursModel extends Model {

	protected $tableName = "joueurs";

	public function __construct() {
		parent::__construct();
	}

	public static function init() {
		$mod = new JoueursModel();
		return $mod;
	}





}


?>