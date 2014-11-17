<?php

namespace Services\Profil;

use Bundles\Parametres\Conf;

use Bundles\Bdd\Model;
use Bundles\Bdd\Db;

use WishList\Models\UserModel;

use Services\Encryptor\Encryptor;
use Services\Pictures\ConvertImg;
use Services\Timer\Timer;
use Services\Mails\Mails;

class Profil {


	protected $db;
	protected $fieldsTable;

	protected $login;
	protected $oldPwd;
	protected $pwd;
	protected $pwd2;
	protected $email;
	protected $activationCode;
	protected $avatar;

	public function __construct() {
		$this->db = Db::init("user");
	}

	public static function isConnected() {
		return !empty($_SESSION['user']['id']);
	}

	public static function disconnect() {
		self::getErrorMessage('Vous êtes maintenant déconnecté.');
		unset($_SESSION['user']);
	}

	// $post = $_POST
	/**
	 * Inscription
	 * 
	 * @param  [array] $post array with "login" (string), "pwd" (string), "pwd2" (string), "email" (string), "activationCode" (bolean), "avatar" (bolean)
	 * @return [array]       formatted result for inscription
	 */
	protected function editPrepare ($post) {
		foreach ($post as $key => $value) {
			$this->$key = $value;
		}

		// set login
		$login = $this->login = $this->formatLogin($this->login, $this->email);
		
		// set pwd2
		$pwd2 = $this->pwd2 = $this->formatPwd2($this->pwd, $this->pwd2);

		// set pwd
		$pwd = $this->pwd = $this->formatPwd($this->pwd);

		// set activationCode
		$activationCode = $this->activationCode = $this->formatActivationCode($this->activationCode);

		// set avatar
		$avatar = $this->avatar = $this->formatAvatar($this->avatar);

		// set email
		$email = $this->email;

		// set fieldsTable
		$this->formatFieldsTable();

		return compact('login', 'pwd', 'pwd2', 'email', 'activationCode', 'avatar');
	}


	protected function formatLogin($login, $email) {
		if ($login === null) {
			$login = $email;
		}

		return $login;
	}


	protected function formatPwd2($pwd, $pwd2) {
		if ($pwd2 === null) {
			$pwd2 = $pwd;
		}

		return $pwd2;
	}


	protected function formatPwd($pwd) {
		return Encryptor::code(Encryptor::crypt($pwd));
	}


	protected function formatActivationCode($activationCode) {
		return $activationCode ? md5(microtime(TRUE)*100000) : null;
	}


	protected function formatAvatar($avatar) {
		return $avatar ? 'avatarDefault.png' : null;
	}


	protected function formatFieldsTable() {
		$fieldsTable = $this->db->getFields();
		foreach ($fieldsTable as $value) {
			$this->fieldsTable[] = $value['Field'];
		}
	}


	protected function getUserTableField($field) {
		$userTableField = preg_grep ("/$field/i", $this->fieldsTable);
		return array_shift($userTableField);
		
	}


	protected static function getErrorMessage($message) {
		if(isset($_SESSION['message'])) $_SESSION["message"] .= $message;
		else $_SESSION['message'] = $message;
	}


	protected function isExistingUser() {
		// Recherche du nom dans la table user
		$userTableName = $this->getUserTableField('name');
		$this->db->addRule($userTableName, $this->login);
		$model = Model::init($this->db);
		$user = $model->getValues();

		// Joueur existe-t-il
		if (!empty($user)) {var_dump($user);
			self::getErrorMessage('Ce login est déja utilisé.');
			return false;
		}

		// Recherche de l'email dans la table user
		$userTableEmail = $this->getUserTableField('email');
		$this->db->addRule($userTableEmail, $this->email);
		$model = Model::init($this->db);
		$user = $model->getValues();

		// Joueur existe-t-il
		if (!empty($user)) {
			self::getErrorMessage('Cet email est déja utilisé.');
			return false;
		}

		return true;
	}

	/**
	 * Verification de la parité des mots de passe
	 * 
	 * @param  [array] $post values of form
	 * @return [boolean]      
	 */
	protected function checkSamePassword($post) {
		if($post['pwd'] != $this->pwd2) {
			self::getErrorMessage('Mots de passe différents.');
			return false;
		}

		return true;
	}


	/**
	 * Enregistrement de l'avatar
	 * 
	 * @return [boolean]
	 */
	protected function savePicture() {
		if ($this->avatar && $this->getUserTableField('avatar') && $_FILES['avatar']['name'] != '') {
			$this->avatar = ConvertImg::init($this->login, array(200,200))->convertJPG('avatar',AVATARS);
			if (!$this->avatar) {
				self::getErrorMessage('Une erreur s\'est produite lors de l\'enregistrement de votre image.');
				return false;
			}
		}

		return true;
	}


	protected function saveUserEntity() {
		$model = Model::init($this->db);
		// Sauvegarde le nouvel utilisateur dans la db
		$newEntity = array();
		$id = $this->getUserTableField('id');
		if ($id !== null) {
			$newEntity[$id] = null;
		}

		$name = $this->getUserTableField('name');
		if ($name !== null) {
			$newEntity[$name] = $this->login;
		}

		$pwd = $this->getUserTableField('pwd');
		if ($pwd !== null) {
			$newEntity[$pwd] = $this->pwd;
		}

		$email = $this->getUserTableField('email');
		if ($email !== null) {
			$newEntity[$email] = $this->email;
		}

		$avatar = $this->getUserTableField('avatar');
		if ($avatar !== null) {
			$newEntity[$avatar] = $this->avatar;
		}

		$activate = $this->getUserTableField('activate');
		if ($activate !== null) {
			$newEntity[$activate] = $this->activationCode;
		}

		return $model->setValues($newEntity)->save();
	}


	/**
	 * envoi_mail($message,$objet) Envoi d'un mail de confirmation avec code d'activation
	 * $mb_mail
	 * @global $config
	 * @return BOL
	 */
	private function send_mail($type = 'subscribe') {
		switch ($type) {
			case 'forgotPwd':
				$frenchName = 'Mot de passe oublié';
				break;
			
			default:
				$frenchName = 'Inscription';
				break;
		}

		$response = Conf::$response;
		$confServer = Conf::getServer()->getConf();

		$response['login'] = $this->login;
		$response['pwd'] = $this->pwd2;

		if ($this->activationCode !== null) {
			$urlConfirm = Conf::getConstants()->getConf()['URL_CONFIRM_INS'];
			$response['url'] = $urlConfirm . "?log=" . $this->login . "&code=" . $this->activationCode;
			$response['activation'] = $this->activationCode;
		}
		
		$destinataire = $this->email;
		$sujet = $frenchName . ' sur "'.$confServer['name'].'"';
		$message = array($response, $type);
		$headers = array($confServer['name'], Conf::getEmails()->getConf()['webmaster'][0]);

		return Mails::init('html')->sendMail($destinataire,$sujet,$message,$headers);
	}
	

	public function subscription($post) {
		$this->editPrepare($post);

		if ($this->isExistingUser() === false || $this->checkSamePassword($post) === false || $this->savePicture() === false) {
			return false;
		}

		// SAUVEGARDE DANS DB
		$saveDb = $this->saveUserEntity();
		
		// ENVOI D'EMAIL
		$sendMail = $this->send_mail('subscribe');

		if ($saveDb && $sendMail){
			return TRUE;
		}else {
			self::getErrorMessage('Une erreur s\'est produite lors de votre inscription.');
			return FALSE;
		}
	}


	public function active_compte ($login, $code) {
		// set fieldsTable
		$this->formatFieldsTable();

		// Recherche du nom dans la table user
		$userTableName = $this->getUserTableField('name');
		$this->db->addRule($userTableName, $login);
		$model = Model::init($this->db);
		$user = $model->getValues();

		$user = array_shift($user);
		$activateField = $this->getUserTableField('activate');

		// Joueur existe-t-il
		if ($user === null || empty($code) || $user[$activateField] != $code) {
			self::getErrorMessage('Les informations données ne correspondent pas à votre inscription.');
			return false;
		}

		$user[$activateField] = 1;
		return $model->setValues($user)->save();
	}


	public function connection($post) {
		// set fieldsTable
		$this->formatFieldsTable();

		// Recherche du nom dans la table user
		$userTableName = $this->getUserTableField('name');
		$this->db->addRule($userTableName, $post['login']);
		$model = Model::init($this->db);
		$user = $model->getValues();

		$user = array_shift($user);

		if ($user === null) {
			$userTableEmail = $this->getUserTableField('email');
			$this->db->addRule($userTableEmail, $post['login']);
			$model = Model::init($this->db);
			$user = $model->getValues();
			$user = array_shift($user);
		}

		if ($user === null) {
			self::getErrorMessage('Login ou mot de passe incorrect.');
			return false;
		}

		$activateField = $this->getUserTableField('activate');
		if ($activateField !== null && $user[$activateField] != 1) {
			self::getErrorMessage('Veuillez tout d\'abord activer votre compte.');
			return false;
		}

		// Verification du mot de passe
		$userTablePwd = $this->getUserTableField('pwd');
		$mdp = Encryptor::crypt($post['pwd']);
		$mdp2 = Encryptor::decode($user[$userTablePwd]);

		if($mdp != $mdp2) {
			self::getErrorMessage('Login ou mot de passe incorrect.');
			return false;
		}

		$_SESSION['user']['id'] = $user[$this->getUserTableField('id')];
		$_SESSION['user']['name'] = $user[$this->getUserTableField('name')];
		$_SESSION['user']['email'] = $user[$this->getUserTableField('email')];
		return true;
	}



	public function update($post){

		// set fieldsTable
		$this->formatFieldsTable();

		// Recherche du nom dans la table user
		$userTableId = $this->getUserTableField('id');
		$this->db->addRule($userTableId, $_SESSION['user']['id']);
		$model = Model::init($this->db);
		$user = $model->getValues();

		$user = array_shift($user);
		if ($user === false) {
			self::getErrorMessage('Une erreur est survenue.');
			return false;
		}
		
		$fields = array_keys($post);
		foreach ($post as $key => $value) {
			$this->$key = $value;
		}

		if (in_array('login', $fields) && $this->login != '' && $this->login != $user[$this->getUserTableField('name')]) {
			$user[$this->getUserTableField('name')] = $this->login;
		}

		if (in_array('email', $fields) && $this->email != '' && $this->email != $user[$this->getUserTableField('email')]) {
			// Recherche de l'email dans la table user
			$userTableEmail = $this->getUserTableField('email');
			$this->db->addRule($userTableEmail, $this->email);
			$model2 = Model::init($this->db);
			$user2 = $model2->getValues();

			// Email existe-t-il
			if (empty($user2)) {
				$user[$this->getUserTableField('email')] = $this->email;
			} else {
				self::getErrorMessage('Email déja associé à un autre compte.');
				return false;
			}
		}

		if (in_array('oldPwd', $fields) && $this->pwd != '' && $this->checkSamePassword($post)) {
			$userTablePwd = $this->getUserTableField('pwd');
			$mdp = Encryptor::crypt($this->oldPwd);
			$mdp2 = Encryptor::decode($user[$userTablePwd]);
			
			if ($mdp == $mdp2) {
				$user[$userTablePwd] = $this->formatPwd($this->pwd);
			} else {
				self::getErrorMessage('Le mot de passe ne correspond pas.');
				return false;
			}
		} elseif (in_array('oldPwd', $fields) && !$this->checkSamePassword($post)) {
			self::getErrorMessage('Les deux mots de passe ne sont pas identiques.');
			return false;
		}

		if (in_array('avatar', $fields) && !empty($this->avatar) && $this->savePicture()) {
			$user[$this->getUserTableField('avatar')] = $this->formatAvatar($this->avatar);
		}

		if (!$model->setValues($user)->save()) {
			self::getErrorMessage('une erreur est survenue lors de la modification de votre profil.');
			return false;
		}
		
		$_SESSION['user']['id'] = $user[$this->getUserTableField('id')];
		$_SESSION['user']['name'] = $user[$this->getUserTableField('name')];
		$_SESSION['user']['email'] = $user[$this->getUserTableField('email')];
		return true;
	}






	public function forgot_pwd($post) {
		// set fieldsTable
		$this->formatFieldsTable();

		$userTableEmail = $this->getUserTableField('email');
		$this->db->addRule($userTableEmail, $post['email']);
		$model = Model::init($this->db);
		$user = $model->getValues();
		$user = array_shift($user);
		
		if ($user === null) {
			self::getErrorMessage('email incorrect.');
			return false;
		}

  		// Creation d'un nouveau mot de passe
  		$newPwd = Encryptor::newPwd();

		$userTablePwd = $this->getUserTableField('pwd');
		$user[$userTablePwd] = $newPwd['encodePwd'];

		// Enregistrement en BD
		if (!$model->setValues($user)->save()) {
			self::getErrorMessage('une erreur est survenue lors de l\'envoi de l\'email 1.');
			return false;
		} else {
			$userTableLogin = $this->getUserTableField('name');
			if ($userTableLogin === null) {
				$userTableLogin = $this->getUserTableField('email');
			}
			$this->login =  $user[$userTableLogin];
			
			$this->pwd2 = $newPwd['newPwd'];
			$this->email = $this->getUserTableField('email');

			// Envoi d'email
			if (!$this->send_mail('forgotPwd')) {
				self::getErrorMessage('une erreur est survenue lors de l\'envoi de l\'email 2.');
				return false;
			}
		}

		return true;
	}


}




?>