<?php


namespace Bundles\Parametres;

use Exception;

class Conf {

	private $files = array(
		'framework' => '../Bundles/Parametres/conf.json'
	);

	private $config;

	private static $server;

	private static $route;

	private static $constants;

	private static $emails;

	private static $links;

	private static $appName = '';

	private static $translateType = 'bdd';

	public static $response = array();

	public function __construct($files) {
		session_start();
		$this->files = array_merge($this->files, $files);
		
		try {
			// Charge les fichiers
			$this->loadFiles();

			// Verifie l'environnement PROD OU DEV
			$this->checkEnvironment();

			// recupere le nom de l'app
			$this->setAppName();

			// prepare les constantes du framework
			$this->predefineFWConstants();

			// Verifie le serveur
			$this->checkServer();

			// Verifie le la route
			$this->checkRoute();

			// Verifie les emails
			$this->checkEmails();

			// determine le timezone
			$this->checkTimezone();

			// prepare les constantes de l'app
			$this->predefineConstants();

			// Verifie la langue
			$this->checkLang();

		} catch (Exception $e) {
			header("HTTP/1.1 404 Not Found");
  			echo file_get_contents(__DIR__."error/404.html"); 
  			var_dump($e);
  			exit();
		}
	}


	public static function init($files) {
		$conf = new Conf($files);
		return $conf;
	}



	private function loadFiles() {
		$this->config = new ConfEntity();
		foreach ($this->files as $type => $file) {
			$fileContents = file_get_contents(dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'App'.DIRECTORY_SEPARATOR.$file);
			$this->config->setValue($type, json_decode($fileContents,true));
		}
	}


	private function checkEnvironment() {
		error_reporting(0);
		ini_set ('display_errors', 'Off');  // cache les erreurs et les fonctions obsoletes en mode developpement
		ini_set ('html_errors', 'Off');  // cache les erreurs et les fonctions obsoletes en mode developpement
				
		\ErrorHandling::$env = $this->config->getEnvironment();
  		register_shutdown_function('ErrorHandling::check_others');/* Paramètres */

		\ErrorHandling::$display = $this->config->getLogType();
		set_error_handler('ErrorHandling::' . $this->config->getEnvironment());
	}


	private function setAppName() {
		self::$appName = $this->config->getAppName();
	}


	private function predefineFWConstants() {
		if (!(self::$constants instanceof ConfEntity)) {
			self::$constants = new ConfEntity();
		}
		foreach ($this->config->getFrameworkConstantes() as $key => $value) {
			$value = str_replace('APPNAME', self::$appName, $value);
			$test = preg_match_all('~\b[[:upper:]]+\b~', $value, $m);
			if($test) {
				$m = array_unique($m[0]);
				foreach ($m as $const) {
					$value = str_replace($const, '$this->'.$const, $value );
				}
			}
			eval("\$this->$key = \$val = $value;");
			self::$constants->setValue($key, $val);
		}
	}


	private function checkServer() {
		self::$server = new ConfEntity();
		foreach ($this->config->getServeurs() as $os) {
			if ($_SERVER['SERVER_NAME']==$os["host"]) {
				self::$server->addConf($os);
				self::$server->setValue('href', $os["protocole"] . '://' . $os['host'] . '/');
				return true;
			}
		}
		throw new Exception('Probleme de serveur.');
	}


	private function checkRoute() {
		// Créé le tableau permettant la creation des CONSTANTES
		if (!(self::$constants instanceof ConfEntity)) {
			self::$constants = new ConfEntity();
		}
		self::$links = new ConfEntity();
		foreach ($this->config->getRouting() as $page => $route) {
			$url = self::$server->getHref() . substr($route["url"],1); 
			if(isset($route["constant"])) self::$constants->setValue($route["constant"], $url);
			self::$links->setValue($page, $url);
		}
		
		// Enregistre les informations de la route en cours
		foreach ($this->config->getRouting() as $route) { 
			$url = '/^\\' . $route["url"] . '$/';
			$urlMatch = preg_match($url, $_SERVER['REQUEST_URI']);
			if ($urlMatch) {
				preg_match_all('/\([^)]*\)/', $route["url"], $matches);
				$urlParts = str_replace($matches[0], '???', $route["url"]);

				$urlPartsArray = explode('???', $urlParts);
				$a = [$_SERVER['REQUEST_URI']];
				$vars = [];
				foreach ($urlPartsArray as $urlPart) {
					if($urlPart != '') {
						$urlPart = str_replace('\?', '?', $urlPart);
						$p = explode($urlPart, $a[count($a)-1]);
						if($p[0] != '') $vars[] = $p[0];
						$a = array_merge($a,$p);
					}
				}
				if($a[count($a)-1] != '') $vars[] = $a[count($a)-1];
				$route['vars'] = $vars;
				$route['url'] = $_SERVER['REQUEST_URI'];
				self::$route = new ConfEntity($route);
				return true;
			}
		}
	}


	private function checkEmails() {
		if (!(self::$emails instanceof ConfEntity)) {
			self::$emails = new ConfEntity();
		}
		self::$emails->addConf($this->config->getEmails());
	}


	private function checkTimezone() {
		date_default_timezone_set($this->config->getTimezone());
	}


	private function predefineConstants() {
		if (!(self::$constants instanceof ConfEntity)) {
			self::$constants = new ConfEntity();
		}
		foreach ($this->config->getConstantes() as $key => $value) {
			$test = preg_match_all('~\b[[:upper:]]+\b~', $value, $m);
			if($test) {
				$m = array_unique($m[0]);
				foreach ($m as $const) {
					$value = str_replace($const, '$this->'.$const, $value );
				}
			}
			eval("\$this->$key = \$val = $value;");
			self::$constants->setValue($key, $val);
		}
	}


	private function setTranslateType() {
		if ($this->config->getTranslateType() !== false) {
			self::$translateType = $this->config->getTranslateType();
		}
	}


	private function checkLang() {
		$this->setTranslateType();
		// Mise en place de la session "lang" qui permet de definir la langue lors de l'affichage
		if(!isset($_SESSION['lang'])) {
			$_SESSION['lang'] = "fr";
		}


		if(isset($_POST["lang_fr"])) {
			$_SESSION["lang"] = "fr";
			$monUrl = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; 
			header("location:".$monUrl);
			exit();
			
		}

		if(isset($_POST["lang_en"])) {
			$_SESSION["lang"] = "en";
			$monUrl = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; 
			header("location:".$monUrl);
			exit();
		}
	}
	

	public static function __callStatic($method, $arguments) {
		$argument = lcfirst(substr($method,3)); 
		if (isset(self::$$argument) && self::$$argument !== null) {
			return self::$$argument;
		} 

		//throw new Exception('vous avez appelez la methode $method mais elle ne semble pas exister.');
		
		return false;
	}




}


?>