<?php


namespace Bundles\Parametres;

class Conf {

	private $files = array();

	private $config;

	private static $server;

	private static $route;

	private static $constants;

	private static $emails;

	private static $links;

	private static $appName = '';

	public static $response = array();

	public function __construct($files) {
		session_start();
		$this->files = $files;
		ini_set ('display_errors', 'On');
		try {
			// Charge les fichiers
			$this->loadFiles();

			// Verifie l'environnement PROD OU DEV
			$this->checkEnvironment();

			// Verifie le serveur
			$this->checkServer();

			// Verifie le la route
			$this->checkRoute();

			// recupere le nom de l'app
			$this->setAppName();

			// Verifie les emails
			$this->checkEmails();

			// determine le timezone
			$this->checkTimezone();

			// prepare les constantes
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
		switch ($this->config->getEnvironment()) {
			case 'PROD':
				error_reporting(0);
				set_error_handler(function() { $this->userErrorHandler(); } );
				break;
			
			default:
				error_reporting(E_ALL|E_STRICT);
				ini_set ('display_errors', 'On');  // affiche les erreurs et les fonctions obsoletes en mode developpement
				ini_set('error_log',__DIR__."/../../php_error.log"); // enregistre les erreurs dans un fichier 'php_error.log'
				break;
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

	private function setAppName() {
		self::$appName = $this->config->getAppName();
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
		foreach ($this->config->getConstantes()["folders"] as $key => $value) {
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



	private function checkLang() {
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






	

	private function userErrorHandler($errno, $errmsg, $filename, $linenum, $vars){
		// Date et heure de l'erreur
		$dt = date("Y-m-d H:i:s (T)");

		// Définit un tableau associatif avec les chaînes d'erreur
		// En fait, les seuls niveaux qui nous interessent
		// sont E_WARNING, E_NOTICE, E_USER_ERROR,
		// E_USER_WARNING et E_USER_NOTICE
		$errortype = array (
			E_ERROR => "Erreur",
			E_WARNING => "Alerte",
			E_PARSE => "Erreur d'analyse",
			E_NOTICE => "Note",
			E_CORE_ERROR => "Core Error",
			E_CORE_WARNING => "CoreWarning",
			E_COMPILE_ERROR => "Compile Error",
			E_COMPILE_WARNING => "Compile Warning",
			E_USER_ERROR => "Erreur spécifique",
			E_USER_WARNING => "Alerte spécifique",
			E_USER_NOTICE => "Note spécifique",
			E_STRICT => "Runtime Notice"
		);


		// Les niveaux qui seront enregistrés
		$user_errors = array(E_ERROR, E_WARNING, E_PARSE, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);
		$err = "<errorentry>\n";
		$err .= "\t<datetime>" . $dt . "</datetime>\n";
		$err .= "\t<errornum>" . $errno . "</errornum>\n";
		$err .= "\t<errortype>" . $errortype[$errno] . "</errortype>\n";
		$err .= "\t<errormsg>" . $errmsg . "</errormsg>\n";
		$err .= "\t<scriptname>" . $filename . "</scriptname>\n";
		$err .= "\t<scriptlinenum>" . $linenum . "</scriptlinenum>\n";
		if (in_array($errno, $user_errors)) {
		$err .= "\t<vartrace>".wddx_serialize_value($vars,"Variables")."</vartrace>\n";
		}
		$err .= "</errorentry>\n\n";

		// sauvegarde de l'erreur, et mail si c'est critique
		error_log($err, 3, __DIR__."/../../error.xml");
		if ($errno == E_USER_ERROR ||  $errno == E_USER_WARNING ||  $errno == E_USER_NOTICE) {
			mail("stephane.pecqueur@gmail.com",$errno,$err);
			//echo "<p>Erreur utilisateur critique !</p>";
			/*header("Location: ".URL_ERR);*/
			header("HTTP/1.1 500 Internal Server Error");
			echo file_get_contents( __DIR__."/../../www/error/500.html"); 
			exit();
		}
	}




	public function __callStatic($method, $arguments) {
		$argument = lcfirst(substr($method,3)); 
		if (isset(self::$$argument) && self::$$argument !== null) {
			return self::$$argument;
		} 
		

		throw new Exception('vous avez appelez la methode $method mais elle ne semble pas exister.');
		
		return false;
	}




}


?>