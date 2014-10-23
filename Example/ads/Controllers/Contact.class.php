<?php

namespace ads\Controllers;

use Bundles\Formulaires\Forms;
use ads\Bundles\Mailer\Mailer;
/**
* 
*/
class Contact {
	
	public function show() {
		$response = array();

		$response['isMailSent'] = false;
		$forgotPwdForm = Forms::make('Contact');
		if(!$forgotPwdForm->isValid()) {
			$response['formContact'] = $forgotPwdForm->render();
		} else {
			$isMailSent = Mailer::sendToMe(
				$forgotPwdForm->getValue('email'), 
				$forgotPwdForm->getValue('name'), 
				$forgotPwdForm->getValue('message')
			);
			if (!$isMailSent) {
				$response['formContact'] = $forgotPwdForm->render();
			}
			$response['isMailSent'] = $isMailSent;
		}
	
		return $response;
	}

}



?>