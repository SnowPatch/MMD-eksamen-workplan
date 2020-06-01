<?php 

namespace Milkshake\System; 

class Loader {

	private static $locations = [
		'system', 
		'model', 
		'controller', 
	];
	
	private static function autoload($dir) {
		
		foreach(glob($dir."/*") as $item) {
			
			$isPHP = strtolower(substr($item, -4)) === ".php";

			if (is_file($item) && $isPHP) {
				
				require_once $item;
				
			} elseif (is_dir($item)) {
				
				Loader::autoload($item);
				
			}
			
		}
		
	}
	
	public static function init() {
		
		/* Load first */
		require_once 'system/Milkshake.php';
		require_once 'system/Router.php';

		/* Autoload from folders */
		foreach (Loader::$locations as $dir) {
			Loader::autoload($dir);
		}

		/* Loadd config files */
		require_once 'routes.php';
		require_once 'config.php';
		
	}

}

?>