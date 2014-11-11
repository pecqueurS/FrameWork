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
	protected $pwd;
	protected $pwd2;
	protected $email;
	protected $activationCode;
	protected $avatar;

	public function __construct() {
		$this->db = Db::init("user");
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


	protected function getErrorMessage($message) {
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
			$this->getErrorMessage('Ce login est déja utilisé.');
			return false;
		}

		// Recherche de l'email dans la table user
		$userTableEmail = $this->getUserTableField('email');
		$this->db->addRule($userTableEmail, $this->email);
		$model = Model::init($this->db);
		$user = $model->getValues();

		// Joueur existe-t-il
		if (!empty($user)) {
			$this->getErrorMessage('Cet email est déja utilisé.');
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
			$this->getErrorMessage('Mots de passe différents.');
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
				$this->getErrorMessage('Une erreur s\'est produite lors de l\'enregistrement de votre image.');
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
	private function send_mail() {
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
		$sujet = 'Inscription sur "'.$confServer['name'].'"';
		$message = array($response, 'inscription');
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
		$sendMail = $this->send_mail();

		if ($saveDb && $sendMail){
			return TRUE;
		}else {
			$this->getErrorMessage('Une erreur s\'est produite lors de votre inscription.');
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
			$this->getErrorMessage('Les informations données ne correspondent pas à votre inscription.');
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
			$this->db->addRule($userTableEmail, $login);
			$model = Model::init($this->db);
			$user = $model->getValues();
			$user = array_shift($user);
		}

		if ($user === null) {
			$this->getErrorMessage('Login ou mot de passe incorrect.');
			return false;
		}

		$activateField = $this->getUserTableField('activate');
		if ($activateField !== null && $user[$activateField] != 1) {
			$this->getErrorMessage('Veuillez tout d\'abord activer votre compte.');
			return false;
		}

		// Verification du mot de passe
		$userTablePwd = $this->getUserTableField('pwd');
		$mdp = Encryptor::crypt($post['pwd']);
		$mdp2 = Encryptor::decode($user[$userTablePwd]);

		if($mdp != $mdp2) {
			$this->getErrorMessage('Login ou mot de passe incorrect.');
			return false;
		}

		$_SESSION['user'] = $user;
		return true;
	}























	public function forgot_pwd($post) {

		// Initialisation du profil
		$profil = ProfilModel::init();

		// Infos Joueur
		$result = $profil->infosPlayer($post["login"])->getValues();

		// Si l'email ne correspond pas a la source
	  	if(empty($result) || $result[0]["jou_email"] != $post["email"]) return false;

	  	else {
	  		// Creation d'un nouveau mot de passe
	  		$newPwd = Encryptor::newPwd();

			/*MODIFICATION MDP*/
			$result2 = $profil->setPlayer([$newPwd['encodePwd']], ['jou_mdp'], $result[0]['jou_id']);

			$response = Conf::$response;

			$response['login'] = $post["login"];
			$response['pwd'] = $newPwd['newPwd'];


			/*ENVOI D'EMAIL*/
			$destinataire = $result[0]["jou_email"];
			$sujet = 'Modification du mot de passe sur "'.Conf::$server['name'].'"';
			$message = array($response, 'forgotPwd');
			$headers = array(Conf::$server['name'], Conf::$emails['webmaster'][0]);

			if (Mails::init('html')->sendMail($destinataire,$sujet,$message,$headers) === TRUE && $result2){
				return TRUE;
			}else {
				return FALSE;
			}



	  	}

	  	


	}








	public function deconnect(){

		$bdd = new BDD();
			$id_joueur = $_SESSION["joueur"]["jou_id"];

		/*MODIFICATION TABLE VERIF_CONNECTIONS*/
			$sql = "UPDATE verif_connections SET vec_deconnect = CURRENT_TIMESTAMP WHERE vec_deconnect IS NULL AND vec_joueurs_id = ? ";

		    $bind = "i";
		  	$arr = array($id_joueur);
		  
		  	$bdd->prepare($sql,$bind);
		  	$result1 = $bdd->execute($arr);
	
		/*SUPPRESSION JOUEUR dans TABLE CONNECTES*/
			$sql = "DELETE FROM connectes WHERE con_joueurs_id = ? ";

		    $bind = "i";
		  	$arr = array($id_joueur);
		  
		  	$bdd->prepare($sql,$bind);
		  	$result2 = $bdd->execute($arr);


			unset($_SESSION["joueur"]);
			unset($_SESSION["partie"]);

			$_SESSION["message"] = "Vous êtes Deconnecté.";

	}







	public function verif_connect(){
		if(isset($_SESSION["joueur"])) $id_joueur = $_SESSION["joueur"]["jou_id"];
		else header("location:".URL_ACCUEIL);

		/*CHERCHE INFO DE CONNEXION*/
		$bdd = new BDD();
			$sql = "SELECT con_id FROM connectes WHERE con_joueurs_id = ? ";

		    $bind = "i";
		  	$arr = array($id_joueur);
		  
		  	$bdd->prepare($sql,$bind);
		  	$result = $bdd->execute($arr);
	
		  	if(empty($result)) header("location:".URL_ACCUEIL);
	}







	public function update_profil($type,$post=array()){


		switch ($type) {
			case 'avatar':
				$this->post["login"] = $_SESSION["joueur"]["jou_login"];
				// FICHIERS
				$fichier_final = "";
				if ($_FILES["avatar"]["name"] != "") {
					// Traiter le fichier envoyé
					
					$erreur = "";
					$taille_maxi = 8000000;
					$taille = filesize($_FILES['avatar']['tmp_name']);
					/** Poids <8Mo **/
					if($taille>$taille_maxi) {
						$erreur .= 'Le fichier est trop gros'.ini_get('post_max_size').' Maximum.<br>';
						$_SESSION['message'] = $erreur;
						$fichier_final = "avatarDefault.png";
					}

					/** Type = Image **/
					if (strpos (  $_FILES['avatar']['type'] ,  'image' )!= FALSE){
						$erreur .= 'Le type de fichier n\'est pas pris en compte ou le fichier est corrompu.<br>';
						$_SESSION['message'] = $erreur;
						$fichier_final = "avatarDefault.png";
					}
					 
					// Envoi les erreurs ou alors converti l'image envoyé par l'utilisateur
					if ($erreur !== "") {
						$_SESSION['message'] = $erreur;
						$fichier_final = "avatarDefault.png";
					} else {
					    $fichier_final = $this->convertJPG($_FILES['avatar']['tmp_name'],AVATARS);
					}
					
				} else {
					$fichier_final = "avatarDefault.png";
				} 


				$update = $fichier_final;
				$_SESSION["joueur"]["jou_avatar"] = $update;

				break;
			
			case 'mdp':


	  			$mdp = $this->algo ($post["mdpOld"]);
				$mdp2 = $this->decode($_SESSION["joueur"]["jou_mdp"]);


				if($mdp == $mdp2) {
					$mdp_final = $this->code ($this->algo ($post["mdpNew"]));
					if(isset($_SESSION["message"])) $_SESSION["message"] .= "Le mot de passe a été modifié. C'est maintenant : ".$post["mdpNew"];
					else $_SESSION["message"] = "Le mot de passe a été modifié. C'est maintenant : ".$post["mdpNew"];
				}
				
				else {
					$mdp_final = $_SESSION["joueur"]["jou_mdp"];
					if(isset($_SESSION["message"])) $_SESSION["message"] .= "Le mot de passe n'a pas été modifé";
					else $_SESSION["message"] = "Le mot de passe n'a pas été modifé";
				}




				$update = $mdp_final;
				$_SESSION["joueur"]["jou_mdp"] = $update;

				break;


			case 'email':


	  			$email = $post["emailOld"];
				$email2 = $_SESSION["joueur"]["jou_email"];

				if($email == $email2) {
					$email_final = $post["emailNew"];
					if(isset($_SESSION["message"])) $_SESSION["message"] .= "L'email a été modifié. C'est maintenant : ".$post["emailNew"];
					else $_SESSION["message"] = "L'email a été modifié. C'est maintenant : ".$post["emailNew"];
				}
				
				else {
					$email_final = $_SESSION["joueur"]["jou_email"];
					if(isset($_SESSION["message"])) $_SESSION["message"] .= "L'email n'a pas été modifé";
					else $_SESSION["message"] = "L'email n'a pas été modifé";
				}

				$update = $email_final;
				$_SESSION["joueur"]["jou_email"] = $update;

				break;


			default:
				$type = false;
				break;
		}


		if($type != false) {
			$jou_id = $_SESSION["joueur"]["jou_id"];
			$bdd = new BDD();


			$sql = "UPDATE joueurs SET jou_$type = ? WHERE jou_id = ? ";

		    $bind = "si";
		  	$arr = array($update,$jou_id);
		  
		  	$bdd->prepare($sql,$bind);
		  	$result = $bdd->execute($arr);

		}




	}






	








}




?>