<?php

Abstract class ErrorHandling {

	static private $now;

	static private $errno;
	static private $errmsg;
	static private $filename;
	static private $linenum;
	static private $vars;

	const ERR_VARS = 'ERR_VARS';
	const ERR_TRACES = 'ERR_TRACES';
	const ERR_ALL = 'ERR_ALL';
	const ERR_MIN = 'ERR_MIN';

	static public $display = 'ERR_ALL';

	const DEV = 'DEV';
	const PROD = 'PROD';

	static public $env = 'DEV';

	static private $errortype = array (
		E_ERROR => "Erreur",
		E_WARNING => "Alerte",
		E_PARSE => "Erreur d'analyse",
		E_NOTICE => "Note",
		E_CORE_ERROR => "Core Error",
		E_CORE_WARNING => "CoreWarning",
		E_COMPILE_ERROR => "Compile Error",
		E_COMPILE_WARNING => "Compile Warning",
		E_USER_ERROR => "Erreur specifique",
		E_USER_WARNING => "Alerte specifique",
		E_USER_NOTICE => "Note specifique",
		E_STRICT => "Runtime Notice"
	);


	static private $filePath = "/../log/";



	public static function PROD($errno, $errmsg, $filename, $linenum, $vars) {
		self::setDate();
		self::setError($errno, $errmsg, $filename, $linenum, $vars);

		self::saveToXML();
		self::saveToLOG();
	
		self::redirectURL();
	}


	public static function DEV($errno, $errmsg, $filename, $linenum, $vars) {
		self::setDate();
		self::setError($errno, $errmsg, $filename, $linenum, $vars);

		self::saveToLOG();
		self::displayErrors();
	}


	/**
	* Checks for a fatal error, work around for set_error_handler not working on fatal errors.
	*/
	public static function check_others() {
		self::$display = self::ERR_MIN;
	  	$error = error_get_last();
	  	self::$env == 'DEV' 
		  	? self::DEV($error["type"], $error["message"], $error["file"], $error["line"], null) 
		  	: self::PROD($error["type"], $error["message"], $error["file"], $error["line"], null);
	    
	}



	private static function setDate() {
		self::$now = date("Y-m-d H:i:s T");
	}


	private static function setError($errno, $errmsg, $filename, $linenum, $vars) {
		self::$errno = $errno;
		self::$errmsg = $errmsg;
		self::$filename = $filename;
		self::$linenum = $linenum;
		self::$vars = $vars;
	}
 

    private static function saveToXML() {
		// Les niveaux qui seront enregistrés
		$user_errors = array(E_ERROR, E_WARNING, E_PARSE, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);

		$err = "<errorentry>\n";
		$err .= "\t<datetime>" . self::$now . "</datetime>\n";
		$err .= "\t<errornum>" . self::$errno . "</errornum>\n";
		$err .= "\t<errortype>" . self::$errortype[self::$errno] . "</errortype>\n";
		$err .= "\t<errormsg>" . self::$errmsg . "</errormsg>\n";
		$err .= "\t<scriptname>" . self::$filename . "</scriptname>\n";
		$err .= "\t<scriptlinenum>" . self::$linenum . "</scriptlinenum>\n";
		if (in_array(self::$errno, $user_errors)) {
			$err .= "\t<vartrace>".wddx_serialize_value(self::$vars,"Variables")."</vartrace>\n";
		}
		$err .= "</errorentry>\n\n";

		// sauvegarde type XML
		error_log($err, 3, dirname(__DIR__).self::$filePath."error.xml");
    }


    private static function saveToLOG() {
		// Les niveaux qui seront enregistrés
		$user_errors = array(E_ERROR, E_WARNING, E_PARSE, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);

		$err = "********************************************************************************\n";
		$err .= "[" . self::$now . "] " . self::$errno . '.' . self::$errortype[self::$errno] . " :\n";
		$err .= self::$errmsg . "\n";
		$err .= 'in ' . self::$filename . ' on line ' . self::$linenum . "\n";
		
		if (self::$display == self::ERR_VARS) {
			$complementaryInformations = self::linearizeVar(self::$vars);
			$err .= "Informations Vars : \n$complementaryInformations\n";
		} elseif (self::$display == self::ERR_TRACES) {
			$stackTrace = implode("\n", self::getStackTrace());
			$err .= "StackTrace : \n$stackTrace\n";
		} elseif (self::$display == self::ERR_ALL) {
			$complementaryInformations = self::linearizeVar(self::$vars);
			$err .= "Informations Vars : \n$complementaryInformations\n";
		
			$stackTrace = implode("\n", self::getStackTrace());
			$err .= "StackTrace : \n$stackTrace\n";
		} else {
			$err .= "\n";
		}

		// sauvegarde type LOG
		error_log($err, 3, dirname(__DIR__).self::$filePath."php_error.log");
    }


    private static function redirectURL() {
    	// Les niveaux qui seront enregistrés
		$user_errors = array(E_ERROR, E_WARNING, E_PARSE, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);

		if (in_array(self::$errno, $user_errors)) {
			header("HTTP/1.1 500 Internal Server Error");
			if (file_exists( __DIR__."/../../www/error/500.html")) {
				echo file_get_contents( __DIR__."/../../www/error/500.html"); 
			}
			
			exit();
		}
	}


	private static function getStackTrace() {
		$stackTrace = [];
		$backtraces = array_reverse(debug_backtrace());

		foreach ($backtraces as $key => $backtrace) {
			if (!empty($backtrace['file']) && strstr($backtrace['file'], 'ErrorHandling') === false) {
			$trace = $key . '. ';
				$trace .= !empty($backtrace['file']) ? $backtrace['file'] : '';
				$trace .= !empty($backtrace['line']) ? "\nLine : " . $backtrace['line'] : '';
				$trace .= !empty($backtrace['class']) && isset($backtrace['function']) && isset($backtrace['type']) ? "\nMethod : " . $backtrace['class'] . $backtrace['type'] . $backtrace['function'] . '()' : '';
				$trace .= !empty($backtrace['args']) ? "\nArguments : \n" . self::linearizeVar($backtrace['args']) : "\n";
				
				$stackTrace[] = $trace;
			}
		}

		return $stackTrace;
	}


	private static function linearizeVar($var, $nbOfLoop = 0) {
		$result = '';
		if (is_object($var)) {
			$var =  (array) $var;
		}

		if (is_array($var)) {
			$j = 0;
			foreach ($var as $key => $value) {
				if ($j != 0) {
					for ($i=0; $i < $nbOfLoop ; $i++) { 
						$result .= "    ";
					}
				}
				$result .= "[$key] => " . self::linearizeVar($value, $nbOfLoop + 1);
				$j++;
			}
		} else {
			$result .= trim((string) $var) . "\n";
		}

		return $result;
	}


	private static function getStackTraceDisplay() {
		$stackTrace = [];
		$backtraces = array_reverse(debug_backtrace());

		foreach ($backtraces as $key => $backtrace) {
			if (!empty($backtrace['file']) && strstr($backtrace['file'], 'ErrorHandling') === false) {
				$trace = '<b>#' . $key . '. </b>';
				$trace .= !empty($backtrace['file']) ? '<b style="color:blue">' . $backtrace['file'] . '</b>' : '';
				$trace .= !empty($backtrace['line']) ? "\n<b> -> Line : <span style='color:blue'>" . $backtrace['line'] . '</span></b>' : '';
				$trace .= !empty($backtrace['class']) && isset($backtrace['function']) && isset($backtrace['type']) ? "\n<b> -> Method : <span style='color:purple'>" . $backtrace['class'] . $backtrace['type'] . $backtrace['function'] . '()</span></b>' : '';
				$trace .= !empty($backtrace['args']) ? "\n<b> -> Arguments : </b>\n" . self::linearizeVarDisplay($backtrace['args']) : "\n";
				
				$stackTrace[] = $trace;
			}
		}

		return $stackTrace;
	}


	private static function linearizeVarDisplay($var, $nbOfLoop = 0) {
		$result = '';
		if (is_object($var)) {
			$var =  (array) $var;
		}

		if (is_array($var)) {
			$j = 0;
			foreach ($var as $key => $value) {
				if ($j != 0) {
					for ($i=0; $i < $nbOfLoop ; $i++) { 
						$result .= "    ";
					}
				}
				$result .= "<b>[<span style='color:blue'>$key</span>]</b> => " . self::linearizeVarDisplay($value, $nbOfLoop + 1);
				$j++;
			}
		} else {
			$result .= '<b style="color:purple">' . trim((string) $var) . "</b>\n";
		}

		return $result;
	}


	private static function displayErrors() {
		echo '<pre style="border:1px solid #aaa; border-radius:5px; background:#eee; padding: 15px;">';
		echo self::showLog();
		echo '</pre>';
	}

    private static function showLog() {
		// Les niveaux qui seront enregistrés
		$user_errors = array(E_ERROR, E_WARNING, E_PARSE, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);

		$err = "<b><span style='color:blue;'>[</span>" . self::$now . "<span style='color:blue;'>]</span> <span style='color:red;'>" . self::$errno . '.' . self::$errortype[self::$errno] . "</span> :</b>\n";
		$err .= '<b style="color:green">' . self::$errmsg . "</b>\n";
		$err .= 'in <b style="color:blue">' . self::$filename . '</b> on line <b style="color:blue">' . self::$linenum . "</b>\n";
		
		if (self::$display == self::ERR_VARS) {
			$complementaryInformations = self::linearizeVarDisplay(self::$vars);
			$err .= "<b>Informations Vars</b> : \n$complementaryInformations\n";
		} elseif (self::$display == self::ERR_TRACES) {
			$stackTrace = implode("\n", self::getStackTraceDisplay());
			$err .= "<b>StackTrace</b> : \n$stackTrace\n";
		} elseif (self::$display == self::ERR_ALL) {
			$complementaryInformations = self::linearizeVarDisplay(self::$vars);
			$err .= "<b>Informations Vars</b> : \n$complementaryInformations\n";
		
			$stackTrace = implode("\n", self::getStackTraceDisplay());
			$err .= "<b>StackTrace</b> : \n$stackTrace\n";
		} else {
			$err .= "\n";
		}

		return $err;
    }
}

?>