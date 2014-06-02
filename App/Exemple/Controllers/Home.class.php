<?php

namespace WOTG\Controllers;

use Bundles\Formulaires\Forms;
use WOTG\Bundles\Profil\Profil;
/**
* 
*/
class Home {
	
	public function __construct() {
		# code...
	}

	public function showHome() {
		$response = array();

		$loginForm = Forms::make('Login');
		if(!$loginForm->isValid()) {
			$response['formLogin'] = $loginForm->render();
		} else {
			$profil = new Profil();
			if($profil->connexion($_POST)){
				echo "bingo";
				//header("location:".URL_PROFIL);
			} else {
				$response['formLogin'] = $loginForm->render();
				echo "pas bingo";
			}
		}
		return $response;
	}

}



?>