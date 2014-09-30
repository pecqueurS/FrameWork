<?php


namespace Bundles\FrontController;

use Bundles\Parametres\Conf;
use Bundles\Templates\Tpl;
use Bundles\Translate\Dico;

class FrontController {

	protected $precall = array (
		'response' => array('Session', 'Urls'),
		'calls' => array('Dico')
	);

	protected $postcall = array (
		'response' => array('Message'),
		'calls' => array('Tpl')
	);

	protected static $page;

	protected $response;

	protected $controller;

	public function __construct() {

		$this->response =& Conf::$response;
		
		// Prepare la route
		$this->checkRoute();

		// Prepare les données communes à toutes les pages
		$this->calls('precall');

		// Lance le controleur de la page demandée
		$this->launchController();
			
		// Prepare les données communes à toutes les pages
		$this->calls('postcall');

	}


	public static function launch() {
		$fc = new FrontController();
		return $fc;
	}



	private function checkRoute() {
		if(Conf::getRoute() !== false) { 
			self::$page = (Conf::getRoute()->getLoadUrl() !== false) ? substr(Conf::getRoute()->getLoadUrl(),1) : substr(Conf::getRoute()->getUrl(),1) ;
		} else {
			$this->notFound();
		}
	}


	private function launchController() {
		$access = preg_replace('/::[a-zA-Z0-9\/_]+$/', '.class.php', Conf::getRoute()->getController());
		$access = str_replace('\\', Conf::getConstants()->getConf()['DS'], $access);
		
		if(is_file(Conf::getConstants()->getConf()['APP'].$access)){
			$routeArr = explode("::", Conf::getRoute()->getController());

			$nsArr = explode("/", $routeArr[0]);
			$class = $nsArr[count($nsArr)-1];

			$method = $routeArr[1];
			require Conf::getConstants()->getConf()['APP'].$access;
			$this->controller = new $class();
			$response = call_user_func_array(array($this->controller, $method), Conf::getRoute()->getVars());
			
			$this->response = array_merge($this->response, $response);
			
		} else {
			$this->notFound();
		}
	}

	private function notFound() {
			// Page inexistante
			header("HTTP/1.1 404 Not Found");
			echo file_get_contents(Conf::getConstants()->getConf()['URL_ERR']); 
			exit();
	}

	
	private function calls($type) {
		$this->initResponse($type);

		$this->initCalls($type);
	}


	private function initResponse($type) {
		$calls = $this->$type;
		foreach ($calls['response'] as $value) {
			$method = 'format' . $value;
			$this->$method();
		}
	}


	private function initCalls($type) {
		$calls = $this->$type;
		foreach ($calls['calls'] as $value) {
			$method = 'call' . $value;
			$this->$method();
		}
	}


	private function formatSession() {
		$this->response["session"] =& $_SESSION;
	}


	private function formatUrls() {
		$this->response["url"] = Conf::getLinks()->getConf();
		$this->response["href"] = Conf::getServer()->getHref() . substr(Conf::getRoute()->getUrl(), 1); 
		$this->response["pageRef"] = self::$page;
	}


	private function formatMessage() {
		// Formattage du message d'erreur
		if(isset($_SESSION['message'])) {
			$this->response["message"] = $_SESSION['message'];
			unset($_SESSION['message']);
		}
	}


	private function callDico() {
		// Initialisation du Dictionnaire
		Dico::init(Conf::getTranslateType());
	}


	private function callTpl() {
		// Affiche la vue
		echo Tpl::display($this->response, "/App/" . Conf::getAppName() . "/Views/Twig_Tpl");
	}


/*









/*

// Appel templates
require_once (TEMPLATES."template.php");*/


















}


?>