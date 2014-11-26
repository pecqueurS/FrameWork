<?php


namespace Services\Timer;


class Timer {

	public static $date;

	public static function initDate($date = null, $format = 'default') {
		if(empty($date)) {
			self::$date = new \DateTime();
		} else {
			switch ($format) {
				case 'fr':
					$format = 'd-m-Y';
					break;
				
				case 'en':
					$format = 'm-d-Y';
					break;
				
				default:
					$format = 'Y-m-d';
					break;
			}
			self::$date = \DateTime::createFromFormat($format, $date);
		}

		return self::$date;

	}

	public static function initDateTime($date = null, $format = 'default') {
		if(empty($date)) {
			self::$date = new \DateTime();
		} else {
			switch ($format) {
				case 'fr':
					$format = 'd-m-Y H:i:s';
					break;
				
				case 'en':
					$format = 'm-d-Y H:i:s';
					break;
				
				default:
					$format = 'Y-m-d H:i:s';
					break;
			}
			self::$date = \DateTime::createFromFormat($format, $date);
		}

		return self::$date;

	}

	public static function formatToDateTimeDB($date = null, $format = 'default') {
		return self::initDateTime($date, $format)->format('Y-m-d H:i:s');
	}

	public static function formatToDateDB($date = null, $format = 'default') {
		return self::initDate($date, $format)->format('Y-m-d');
	}





}


?>