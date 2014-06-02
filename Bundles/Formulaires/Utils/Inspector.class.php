<?php

namespace Bundles\Formulaires\Utils;



/**
* 
*/
class Inspector {

	private static $error;

	public function __construct () {
		self::$error = '';
	}

	public static function checkData($value, $type, $constraint) {
		$inspector = new Inspector();

		$method = $type.'_Check';

		return $inspector->$method($value, $constraint);
	}


	public static function getMsg() {
		return self::$error;
	}


	public function __call($method,$arguments) {
		echo 'Vous avez appelé la méthode ', $method, 'avec les arguments : ', implode(', ',$arguments);
	}







// Contraintes de bases
	private function NotBlank_Check($value, $constraint) {
		if($constraint) {
			if (false === $value || (empty($value) && '0' != $value)) {
	            self::$error = '<span>This value should not be blank. </span>';
	            return false;
	        }
		} 
		return true;
	}

	private function Blank_Check($value, $constraint) {
		if($constraint) {
			if ('' !== $value && null !== $value) {
	            self::$error = '<span>This value should be blank. </span>';
	            return false;
	        }
		} 
		return true;
	}

	private function NotNull_Check($value, $constraint) {
		if($constraint) {
			if (null === $value) {
	            self::$error = '<span>This value should not be null. </span>';
	            return false;
	        }
		} 
		return true;
	}

	private function Null_Check($value, $constraint) {
		if($constraint) {
			if (null !== $value) {
	            self::$error = '<span>This value should be null. </span>';
	            return false;
	        }
		} 
		return true;
	}

	private function True_Check($value, $constraint) {
		if($constraint) {
			if (true !== $value && 1 !== $value && '1' !== $value) {
	            self::$error = '<span>This value should be true. </span>';
	            return false;
	        }
		} 
		return true;
	}

	private function False_Check($value, $constraint) {
		if($constraint) {
			if (null === $value || false === $value || 0 === $value || '0' === $value) {
	            return true;
	        } else {
	            self::$error = '<span>This value should be false. </span>';
	            return false;
	        }
		} 
		return true;
	}

	private function Type_Check($value, $constraint) {
			if (null === $value) {
	            return true;
	        }

	        $type = strtolower($constraint);
	        $type = $type == 'boolean' ? 'bool' : $constraint;
	        $isFunction = 'is_'.$type;
	        $ctypeFunction = 'ctype_'.$type;

	        if (function_exists($isFunction) && call_user_func($isFunction, $value)) {
	            return true;
	        } elseif (function_exists($ctypeFunction) && call_user_func($ctypeFunction, $value)) {
	            return true;
	        } elseif ($value instanceof $constraint->type) {
	            return true;
	        }

	        self::$error = "<span>This value should be of type $constraint. </span>";
	        return false;
	}

// Contraintes sur les chaînes de caractères
	private function Email_Check($value, $constraint) {
		if($constraint) {
			if (null === $value || '' === $value) {
	            return true;
	        }

	        $value = (string) $value;
	        $valid = filter_var($value, FILTER_VALIDATE_EMAIL);

	        if (!$valid) {
	            self::$error = '<span>This value is not a valid email address. </span>';
		        return false;
	        }
	    } 
		return true;    
	}

	private function Length_Check($value, $constraint) {
		if (null === $value || '' === $value) {
            return true;
        }

        $stringValue = (string) $value;

        $length = strlen($stringValue);
        
        if ($constraint['min'] == $constraint['max'] && $length != $constraint['min']) {
            self::$error = "<span>This value should have exactly {$constraint['min']} characters... </span>";
	        return false;
        }
        

        if (null !== $constraint['max'] && $length > $constraint['max']) {
            self::$error = "<span>This value is too long. It should have {$constraint['max']} characters or less... </span>";
	        return false;
        }

        if (null !== $constraint['min'] && $length < $constraint['min']) {
           self::$error = "<span>This value is too short. It should have {$constraint['min']} characters or more... </span>";
	        return false;
        }

		return true;
	        
	}

	private function Url_Check($value, $constraint) {
		if($constraint) {
			if (null === $value || '' === $value) {
	            return true;
	        }

	        $value = (string) $value;
	        $valid = filter_var($value, FILTER_VALIDATE_URL);

	        if (!$valid) {
	            self::$error = '<span>This value is not a valid url. </span>';
		        return false;
	        }
	    } 
		return true; 
    }

	private function Regex_Check($value, $constraint) {
		if (null === $value || '' === $value) {
            return true;
        }

        $value = (string) $value;
        if ($constraint['match']) {
        	if(!preg_match($constraint['pattern'], $value)) {
	            self::$error = '<span>This value is not valid. </span>';
			    return false;
			}
        } else{
			if(preg_match($constraint['pattern'], $value)) {
	            self::$error = '<span>This value is not valid. </span>';
			    return false;
			}
        } 
        return true;
    }

	private function Ip_Check($value, $constraint) {
		if (null === $value || '' === $value) {
            return true;
        }

        $value = (string) $value;

        switch ($constraint['version']) {
            case 'V4':
               $flag = FILTER_FLAG_IPV4;
               break;

            case 'V6':
               $flag = FILTER_FLAG_IPV6;
               break;

            case 'V4_NO_PRIV':
               $flag = FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE;
               break;

            case 'V6_NO_PRIV':
               $flag = FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE;
               break;

            case 'ALL_NO_PRIV':
               $flag = FILTER_FLAG_NO_PRIV_RANGE;
               break;

            case 'V4_NO_RES':
               $flag = FILTER_FLAG_IPV4 | FILTER_FLAG_NO_RES_RANGE;
               break;

            case 'V6_NO_RES':
               $flag = FILTER_FLAG_IPV6 | FILTER_FLAG_NO_RES_RANGE;
               break;

            case 'ALL_NO_RES':
               $flag = FILTER_FLAG_NO_RES_RANGE;
               break;

            case 'V4_ONLY_PUBLIC':
               $flag = FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
               break;

            case 'V6_ONLY_PUBLIC':
               $flag = FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
               break;

            case 'ALL_ONLY_PUBLIC':
               $flag = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
               break;

            default:
                $flag = null;
                break;
        }

        if (!filter_var($value, FILTER_VALIDATE_IP, $flag)) {
            self::$error = '<span>This is not a valid IP address. </span>';
			return false;
        } else {
        	return true;
        }
    }

// Contraintes sur les nombres
	private function Range_Check($value, $constraint) {
		if (null === $value) {
            return true;
        }

        if (!is_numeric($value)) {
            self::$error = "<span>This value should be a valid number. </span>";
	        return false;
        }

        if (null !== $constraint['max'] && $value > $constraint['max']) {
            self::$error = "<span>This value should be {$constraint['max']} or less. </span>";
	        return false;
        }

        if (null !== $constraint->min && $value < $constraint->min) {
            self::$error = "<span>This value should be {$constraint['min']} or more. </span>";
	        return false;
        }
        return true;
    }

// Contraintes comparatives    
	private function EqualTo_Check($value, $constraint) {
		foreach ($constraint as $compare) {
        	if($compare == $value) return true;
        }
        $constraintStr = implode(' or ', $constraint);
        self::$error = "<span>This value should be equal to $constraintStr. </span>";
        return false;
    }

 	private function NotEqualTo_Check($value, $constraint) {
		foreach ($constraint as $compare) {
        	if($compare == $value) {
				$constraintStr = implode(' or ', $constraint);
		        self::$error = "<span>This value should not be equal to $constraintStr. </span>";
		        return false;
        	} 
        }
        return true;
    }

	private function IdenticalTo_Check($value, $constraint) {
		foreach ($constraint as $compare) {
        	if($compare === $value) return true;
        }
        $constraintStr = implode(' or ', $constraint);
        self::$error = "<span>This value should be identical to $constraintStr. </span>";
        return false;
    }

 	private function NotIdenticalTo_Check($value, $constraint) {
		foreach ($constraint as $compare) {
        	if($compare === $value) {
				$constraintStr = implode(' or ', $constraint);
		        self::$error = "<span>This value should not be identical to $constraintStr. </span>";
		        return false;
        	} 
        }
        return true;
    }

	private function LessThan_Check($value, $constraint) {
		if($constraint > $value) return true;
        else {
        	self::$error = "<span>This value should be less than to $constraint. </span>";
        	return false;
        }
    }
        
	private function LessThanOrEqual_Check($value, $constraint) {
		if($constraint >= $value) return true;
        else {
        	self::$error = "<span>This value should be less than or equal to $constraint. </span>";
        	return false;
        }
    }
        
	private function GreaterThan_Check($value, $constraint) {
		if($constraint < $value) return true;
        else {
        	self::$error = "<span>This value should be greater than to $constraint. </span>";
        	return false;
        }
    }
        
	private function GreaterThanOrEqual_Check($value, $constraint) {
		if($constraint <= $value) return true;
        else {
        	self::$error = "<span>This value should be greater than or equal to $constraint. </span>";
        	return false;
        }
    }
        
// Contraintes sur les dates
	private function Date_Check($value, $constraint) {
		if($constraint) {
			if (null === $value || '' === $value || $value instanceof \DateTime) {
	            return true;
	        }

	        $value = (string) $value;

	        switch ($constraint['format']) {
	        	case 'fr':
	        		// jj-mm-aaaa
	        		$pattern = '#^(\d{2})-(\d{2})-(\d{4})$#';
	        		break;
	        	
	        	case 'en':
	        		// mm/jj/aaaa
	        		$pattern = '#^(\d{2})/(\d{2})/(\d{4})$#';
	        		break;
	        	
	        	default:
	        		// aaaa-mm-jj
	        		$pattern = '#^(\d{4})-(\d{2})-(\d{2})$#';
	        		break;
	        	}

	        if (!preg_match($pattern, $value, $matches) || !checkdate($matches[2], $matches[3], $matches[1])) {
	            self::$error = '<span>This value is not a valid date. </span>';
        		return false;
	        }
	    }
    }
        

// Contraintes sur les fichiers
	private function File_Check($value, $constraint) {
		// origin : http://www.php.net/manual/fr/features.file-upload.php#114004
		    // Undefined | Multiple Files | $_FILES Corruption Attack
		    // If this request falls under any of them, treat it invalid.
		    if (
		        !isset($value['upfile']['error']) ||
		        is_array($value['upfile']['error'])
		    ) {
		    	self::$error = '<span>Invalid parameters. </span>';
        		return false;
		    }

		    // Check $_FILES['upfile']['error'] value.
		    switch ($value['upfile']['error']) {
		        case UPLOAD_ERR_OK:
		            break;
		        case UPLOAD_ERR_NO_FILE:
			        self::$error = '<span>No file sent. </span>';
	        		return false;
		        case UPLOAD_ERR_INI_SIZE:
		        case UPLOAD_ERR_FORM_SIZE:
			        self::$error = '<span>Exceeded filesize limit. </span>';
	        		return false;
		        default:
			        self::$error = '<span>Unknown errors. </span>';
	        		return false;
		    }

		    // You should also check filesize here.
		    if ($value['upfile']['size'] > $constraint['maxSize']) {
		        self::$error = '<span>Exceeded filesize limit. </span>';
	        	return false;
		    }

		    // DO NOT TRUST $_FILES['upfile']['mime'] VALUE !!
		    // Check MIME Type by yourself.
		    $finfo = new \finfo(FILEINFO_MIME_TYPE);
		    if (false === $ext = array_search(
		        $finfo->file($value['upfile']['tmp_name']),$constraint['mimeTypes'], true)) {
		        self::$error = '<span>Invalid file format. </span>';
	        	return false;
		    }

		    return true;
		
    }
        

    


}







?>