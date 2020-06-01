<?php 

namespace Milkshake;

class Settings {

	private static $settings = [];
	
	public static function set($key, $value) {
		Settings::$settings[$key] = $value;
	}
	
	public static function get($key) {
		return Settings::$settings[$key];
	}
	
}

?>