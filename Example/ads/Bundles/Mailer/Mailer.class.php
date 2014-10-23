<?php

namespace ads\Bundles\Mailer;

use Services\Mails\Mails;
use Services\Timer\Timer;
use ads\Models\MailsModel;
use Bundles\Parametres\Conf;

/**
* 
*/
class Mailer {

	private $destinataire;
	private $name;
	private $message;
	
	public static function sendToMe($destinataire, $name, $message) {
		$mailer = new Mailer($destinataire, $name, $message);
		$isSaveInDb = $mailer->saveToDb();
		$isSend = $mailer->sendMeAMail();

		return ($isSaveInDb && $isSend);
	}

	public function __construct($destinataire, $name, $message) {
		$this->destinataire = $destinataire;
		$this->name =  $name;
		$this->message = $message;
	}

	public function saveToDb() {
		$saveMail = MailsModel::init()->addMail(
			$this->name, 
			$this->destinataire, 
			$this->message, 
			Timer::formatToDateTimeDB() 
		); // 1 : ok | -1 : ko

		return $saveMail != '-1' ? true : false;
	}

	public function sendMeAMail() {
		/*ENVOI D'EMAIL*/
			$destinataire = Conf::getEmails()->getWebmaster()[0];
			$sujet = 'Contact ADS : '. $this->name;
			$message = $this->message;
			$headers = array(Conf::getServer()->getName(), $this->destinataire);

			return Mails::init('txt')->sendMail($destinataire,$sujet,$message,$headers);
	}

}



?>