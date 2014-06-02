<?php

namespace WOTG\Bundles\Profil;

use Bundles\Bdd\BDD;

class Profil {


	private $post;


	public function __construct() {

	}



	// $post = login, mdp, email
	public function inscription ($post) {
		$this->post = $post;

		$login = $this->post["login"];
		$mdp = $this->code ($this->algo ($this->post["mdp"]));
		$email = $this->post["email"];
		$lang = ($_SESSION["lang"] == "fr") ? 1 : 2 ;

		$code_activation = md5(microtime(TRUE)*100000);










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








		$bdd = new BDD();


		$sql = "SELECT jou_login FROM `joueurs` WHERE jou_login = ? ";

	    $bind = "s";
	  	$arr = array($login);
	  
	  	$bdd->prepare($sql,$bind);
	  	$result = $bdd->execute($arr);




	  	if(empty($result)) {

			/*INSERTION EN BDD DU NOUVEL INSCRIT*/
			$sql = "INSERT INTO `joueurs` VALUES ( NULL,  ? , ? , ? , 0, NULL, 0, NULL, ? , ? , ? , NULL )";

		    $bind = "sssiss";
		  	$arr = array($login, $mdp, $email, $lang, $fichier_final, $code_activation );
		  
		  	$bdd->prepare($sql,$bind);
		  	$result = $bdd->execute($arr);


			$objet = 'Inscription au jeu "Warriors of the Galaxy"';
			$message = 	"Pour teminer votre inscription, veuillez cliquer sur le lien suivant : \n
						".URL_CONFIRM_INS."$log=".$login."&code=".$code_activation." \n 
						\n
						Votre login est : ".$login."\n
						Votre mot de passe : ".$this->post["mdp"];
				
			if (/*$this->envoi_mail($message,$objet)=== TRUE && */$result){
				return TRUE;
			}else {
				return FALSE;
			}





	  	} else {
	  		$_SESSION["message"] = "Ce login est déja utilisé";
	  		return FALSE;
	  	}





	}







	public function active_compte ($login, $code) {
		$bdd = new BDD();

		$sql = "SELECT jou_login, jou_activate  FROM `joueurs` WHERE jou_login = ? ";

	    $bind = "s";
	  	$arr = array($login);
	  
	  	$bdd->prepare($sql,$bind);
	  	$result = $bdd->execute($arr);

	  	if(empty($result) || $result[0]["jou_activate"] != $code) return FALSE;
	  	else {

			$sql = "UPDATE joueurs SET jou_activate = TRUE WHERE jou_login = ? ";

		    $bind = "s";
		  	$arr = array($login);
		  
		  	$bdd->prepare($sql,$bind);
		  	$result = $bdd->execute($arr);


		  	if($result) return true;
		  	else return false;



	  	}
	}







	public function forgot_pwd($post) {

		$bdd = new BDD();

		$sql = "SELECT jou_login, jou_email  FROM `joueurs` WHERE jou_login = ? ";

	    $bind = "s";
	  	$arr = array($post["login"]);
	  
	  	$bdd->prepare($sql,$bind);
	  	$result = $bdd->execute($arr);


	  	if(empty($result) || $result[0]["jou_email"] != $post["email"]) return false;

	  	else {

	  		$login = $post["login"];
			$new_mdp = substr(str_shuffle(str_repeat('0123456789',1)),0,1);
			$new_mdp .= substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ',1)),0,1);
			$new_mdp .= substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz',1)),0,8);
			$new_mdp .= substr(str_shuffle(str_repeat('abcdefghijklmnopqrstuvwxyz',1)),0,1);
			$new_mdp .= substr(str_shuffle(str_repeat('0123456789',1)),0,1);
			$int1= $this->algo ($new_mdp);
			$code_mdp = $this->code ($int1);

			$objet = 'Modification du mot de passe sur "Warriors of the Galaxy"' ;
			$message = 	"Changement de mot de passe : \n
			Votre login est : $login \n
			Votre nouveau mot de passe est : $new_mdp \n
			Vous pouvez modifier ce mot de passe dans la partie 'Mon Compte'";



			/*MODIFICATION MDP*/
			$sql = "UPDATE joueurs SET jou_mdp = '$code_mdp' WHERE jou_login = ? ";

		    $bind = "s";
		  	$arr = array($login);
		  
		  	$bdd->prepare($sql,$bind);
		  	$result = $bdd->execute($arr);


			/*ENVOI D'EMAIL*/
			if (/*$this->envoi_mail($message,$objet)=== TRUE && */$result){
				return TRUE;
			}else {
				return FALSE;
			}




	  	}

	  	


	}





	public function connexion($post) {
		$bdd = new BDD();


		$sql = "SELECT jou_id,jou_login,jou_mdp,jou_email,jou_xp,jou_parties_id,jou_ready,jou_team,jou_langues_id,jou_avatar,jou_activate,jou_races_id FROM `joueurs` WHERE jou_login = ? ";

	    $bind = "s";
	  	$arr = array($post["login"]);
	  
	  	$bdd->prepare($sql,$bind);
	  	$result = $bdd->execute($arr);
		

	  	if(!empty($result)) {
	  			$mdp = $this->algo ($post["mdp"]);
				$mdp2 = $this->decode($result[0]['jou_mdp']);

				if($result[0]['jou_activate'] != 1) {
					if(isset($_SESSION["message"])) $_SESSION["message"] .= "Veuillez tout d'abord activer votre compte.";
					else $_SESSION["message"] = "Veuillez tout d'abord activer votre compte.";

					return FALSE;
				}




				if($mdp != $mdp2) return FALSE;


				$ip_client = $_SERVER['REMOTE_ADDR'];
				$id_joueur = $result[0]["jou_id"];

				/*INSERTION EN BDD DU NOUVEL INSCRIT*/
				$sql = "INSERT INTO `verif_connections` VALUES ( NULL,  ? , CURRENT_TIMESTAMP , ? , NULL , ? )";

			    $bind = "iss";
			  	$arr = array($id_joueur, $ip_client, session_id());
			  
			  	$bdd->prepare($sql,$bind);
			  	$result2 = $bdd->execute($arr);



				$sql = "SELECT con_joueurs_id FROM connectes WHERE con_joueurs_id = ? ";

			    $bind = "i";
			  	$arr = array($id_joueur);
			  
			  	$bdd->prepare($sql,$bind);
			  	$result3 = $bdd->execute($arr);

			  	if(empty($result3)) {

					$sql = "INSERT INTO `connectes` VALUES ( NULL,  ? , CURRENT_TIMESTAMP )";

				    $bind = "i";
				  	$arr = array($id_joueur);
				  
				  	$bdd->prepare($sql,$bind);
				  	$result4 = $bdd->execute($arr);

			  	} else {
			  		$result4 = true;
			  	}


			  	if($result2 && $result4) {
			  		$_SESSION["joueur"] = $result[0];
			  		if($_SESSION["joueur"]["jou_langues_id"]==1) $_SESSION["lang"] = "fr";
			  		else $_SESSION["lang"] = "en";
			  	} else {
					if(isset($_SESSION["message"])) $_SESSION["message"] .= "Une erreur est survenue.";
					else $_SESSION["message"] = "Une erreur est survenue.";

					return FALSE;

			  	}


				


				return TRUE;


	  	} else {
	  		return FALSE;


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





/**
	 * ************** HASHING *******************
	 */	
	
	/**
	 * algo($mdp) algo
	 * Ex : 'jean-paul' -> '5ORJpIwFYJlCIBbBoB'
	 * @param STR $mdp
	 * @return STR
	 */
	private function algo($mdp){
		$arr1 = str_split($mdp);
		$arr2 = array();
		$count = count($arr1);
	
		$lettre = array();
		for ($i=65 ;$i<=90;$i++){
			$lettre[] = chr($i);
		}
		for ($i=48 ;$i<=57;$i++){
			$lettre[] = chr($i);
		}
		for ($i=97 ;$i<=122;$i++){
			$lettre[] = chr($i);
		}
	
		$code_int1 ='';
	
		for ($i=0;$i<$count;$i++){
			$arr1[$i] = ord ($arr1[$i]);
			$arr2[$i] = intval((pow ($i+10, 4)*($i+7))/$arr1[$i]);
			$arr2[$i] = str_pad($arr2[$i], 6, "001", STR_PAD_LEFT);
			$arr3[$i] = str_split($arr2[$i],3);
			$a = ((($arr3[$i][0])%61));
			$b = ((($arr3[$i][1])%61));
	
			$code_int1 .= $lettre[$a];
			$code_int1 .= $lettre[$b];
		}
		$code_int2 = strrev ($code_int1);
	
		return $code_int2;
	}
	
	/**
	 * code($mdp) code
	 * Ex : '5ORJpIwFYJlCIBbBoB' -> 'AKC5OEQORJzi4pIXNqwFszJYJb6alClPCIBFbobBWItoB'
	 * @param STR $mdp
	 * @return STR
	 */
	private function code($mdp){
	
	
		$code_array = str_split($mdp,2);
		$count = count($code_array);
		$code_fini = '';
		for ($i=0;$i<$count;$i++){
			$random = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',3)),0,3);
			$code_fini .= $random.$code_array[$i];
		}
	
		return $code_fini;
	}
	
	/**
	 * decode ($mdp) decode
	 * Ex : 'AKC5OEQORJzi4pIXNqwFszJYJb6alClPCIBFbobBWItoB' -> '5ORJpIwFYJlCIBbBoB'
	 * @param STR $mdp
	 * @return STR
	 */
	private function decode ($mdp){
		$code_array = str_split($mdp,5);
		$count = count($code_array);
		$code_fini = '';
		for ($i=0;$i<$count;$i++){
			$code_fini .= substr($code_array[$i], -2);
		}
		return $code_fini;
	}
	
	
			
	
	




	/**
	 * envoi_mail($message,$objet) Envoi d'un mail de confirmation avec code d'activation
	 * $mb_mail
	 * @global $config
	 * @return BOL
	 */
	private function envoi_mail($message,$objet){
 		global $config;
		$destinataire = $this->post["email"];
 		$sujet = 'Inscription à "My Accounts & Budgets"' ;
 		$entete = "From: stephane.pecqueur@gmail.com" ;
 		
		return mail($destinataire, $objet, $message, $entete) ;
	}
	


	/**
	 * Creation image uploadé
	 */
	public function convertJPG($chemin_img,$dossier_Dest){
		
		switch ( strtolower( pathinfo( $_FILES["avatar"]["name"], PATHINFO_EXTENSION ))) {
	        case 'jpeg':
	        case 'jpg':
	            $source =  imagecreatefromjpeg($chemin_img);
	        break;

	        case 'png':
	            $source =  imagecreatefrompng($chemin_img);
	        break;

	        case 'gif':
	            $source =  imagecreatefromgif($chemin_img);
	        break;
	    }		

		// $source = imagecreatefromjpeg($chemin_img); // La photo est la source

		$image = imagecreatetruecolor(200, 200); // Creation de l'image de sortie

		// Declaration couleur
		$blanc = imagecolorallocate($image, 255, 255, 255);
		// Couleur de fond
		$fond = ImageFilledRectangle ($image,0, 0, imagesx($image),imagesy($image), $blanc);


		// On crée la miniature vide
		// Les fonctions imagesx et imagesy renvoient la largeur et la hauteur d'une image
		$largeur_source = imagesx($source);
		$hauteur_source = imagesy($source);

		$largeur_destination = imagesx($image)-30;
		$hauteur_destination = imagesy($image)-30;

		$marge_supp_left = 0;
		$marge_supp_top = 0;

		if($largeur_source<$hauteur_source) { // SI PORTRAIT
			$rapport = $hauteur_destination/$hauteur_source;
			$futur_largeur = $largeur_source*$rapport;
			$marge_supp_left = round(($largeur_destination-$futur_largeur)/2);
			$largeur_destination = $futur_largeur;
		} else { // SI PAYSAGE ou CARRE
			$rapport = $largeur_destination/$largeur_source;
			$futur_hauteur = $hauteur_source*$rapport;
			$marge_supp_top = round(($hauteur_destination-$futur_hauteur)/2);
			$hauteur_destination = $futur_hauteur;
		}


		$name = $this->post["login"].'.jpg';

		// On crée la miniature
		imagecopyresampled($image, $source, (15+$marge_supp_left), (15+$marge_supp_top), 0, 0, $largeur_destination,$hauteur_destination, $largeur_source,$hauteur_source);

		// On enregistre la miniature sous le nom $name
		imagejpeg($image,$dossier_Dest.$name);

		return $name;

	}




}




?>