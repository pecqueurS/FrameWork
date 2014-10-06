<?php


namespace Services\Timer;


class Timer {

	public static $date;

	public static function init($date = null) {
		if(empty($date)) {
			self::$date = new \DateTime();
		} else {
			self::$date = \DateTime::createFromFormat($date[0], $date[1]);
		}

		return self::$date;

	}

	public static function formatToDateTimeDB($date = null) {
		return self::init($date)->format('Y-m-d H:i:s');
	}

	




}


?>