<?php


namespace Bundles\Parametres;

class Conf {

	private $files = array();

	private $config = array();

	static $server = array();

	static $route = array();

	static $constants = array();

	static $emails = array();

	static $links = array();

	static $appName = 'Exemple';

	public function __construct($files) {
		session_start();
		$this->files = $files;
		
		try {
			// Charge les fichiers
			$this->loadFiles();

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

			// Verifie l'environnement PROD OU DEV
			$this->checkEnvironment();

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
		foreach ($this->files as $type => $file) {
			$fileContents = file_get_contents(__DIR__.$file);
			$this->config[$type] = json_decode($fileContents,true);
		}
	}

	private function checkServer() {
		foreach ($this->config["app"]["serveurs"] as $os) {
			if ($_SERVER['SERVER_NAME']==$os["host"]) {
				self::$server = $os;
				self::$server["href"] = $os["protocole"]."://".$os["host"]."/";
				return true;
			}
		}
		throw new Exception('Probleme de serveur.');
	}

	private function checkRoute() {
		// Créé le tableau permettant la creation des CONSTANTES
		$constants = array();
		foreach ($this->config["routing"] as $page => $route) {
			$url = self::$server["href"].substr($route["url"],1); 
			if(isset($route["constant"])) $constants[$route["constant"]] = $url;
			self::$links[$page] = $url;
		}
		self::$constants = array_merge($constants, self::$constants);

		// Enregistre les informations de la route en cours
		foreach ($this->config["routing"] as $route) {
			if ($_SERVER['REQUEST_URI']==$route["url"]) {
				self::$route = $route;
				return true;
			}
		}
	}

	private function setAppName() {
		self::$appName = $this->config["app"]["appName"];
	}


	private function checkEmails() {
		self::$emails = array_merge($this->config["app"]["emails"], self::$emails);
	}


	private function checkTimezone() {
		date_default_timezone_set($this->config["app"]["timezone"]);
	}


	private function checkEnvironment() {
		switch ($this->config["app"]["environment"]) {
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


	private function predefineConstants() {
		$constants = array();
		foreach ($this->config["app"]["constantes"]["folders"] as $key => $value) {
			$test = preg_match_all('~\b[[:upper:]]+\b~', $value, $m);
			if($test) {
				$m = array_unique($m[0]);
				foreach ($m as $const) {
					$value = str_replace($const, '$this->'.$const, $value );
				}
			}
			eval("\$this->$key = \$val = $value;");
			$constants[$key] = $val;
		}
		self::$constants = array_merge($constants, self::$constants);
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


}


?>