<?php


namespace Services\Mails;

use Services\Mails\Tpl\MailsTpl;

use Bundles\Parametres\Conf;


class Mails {

	private $type;

	public function __construct($type = 'txt') {
		$this->type = $type;
		ini_set("SMTP",Conf::getServer()->getSmtp() );
		ini_set("smtp_port",Conf::getServer()->getSmtp_port() );
		ini_set("sendmail_from",Conf::getEmails()->getWebmaster()[0] );

	}

	public static function init($type = 'txt') {
		$mail = new Mails($type);
		return $mail;
	}

	private function constructHTMLMessage($vars, $matrix) {
		return MailsTpl::display($vars, "$matrix.twig");
	}

	private function constructHeaderMail($name, $email) {
		$headers = "From: \"$name\"<$email>\n";
		$headers .= "Reply-To: \"$name\"<$email>\n";
		switch ($this->type) {
			case 'txt':
				$contentType = 'text/plain';
				break;
			
			case 'html':
				$contentType = 'text/html';
				break;
			
			default:
				$contentType = 'text/plain';
				break;
		}

		return $headers .= "Content-Type: $contentType;\n Content-Transfer-Encoding: 8bit;\n charset=\"UTF-8\"";
	}


	/*
	 * $destinataire = string
	 * $sujet = string
	 * $message = $type->'txt'  => string
	 * 			  	   ->'html' => array ($vars, $matrix)
	 * $headers = array ( $nameExpediteur, $emailExpediteur )
	 * 
	 */
	public function sendMail($destinataire,$sujet,$message,$headers) {
		$headersToSend = $this->constructHeaderMail($headers[0], $headers[1]);
		$messageToSend = ($this->type == 'html' && is_array($message)) ? $this->constructHTMLMessage($message[0], $message[1]) : $message ; 
		//phpinfo();
		if (mail($destinataire,$sujet,$messageToSend,$headersToSend)) {
			return true;
		} else {
			return false;
		}

	}

	




}


?>