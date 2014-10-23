<?php


namespace ads\Models;

use Bundles\Bdd\Db;
use Bundles\Bdd\Model;


class MailsModel extends Model {

	protected $tableName = "mails";
	
	public function __construct() {
		parent::__construct();
	}

	public static function init() {
		$mod = new MailsModel();
		return $mod;
	}

	/*public function infosPlayer($login){
		$this->table = $this->db->addRule("jou_login", $login)
								->select();
		return $this;
	}

	public function setPlayer($values, $fields, $id) {
		return $this->db->addRule("jou_id", $id)
						->update($values, $fields);
	}*/

	public function addMail($name, $email, $message, $time) {
		$values = array( NULL, $name, $email, $message, $time );
		return $this->db->insert($values);
	}




}


?>