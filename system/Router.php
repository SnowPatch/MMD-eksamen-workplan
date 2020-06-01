<?php 

namespace Milkshake\System;

class Router {

	private static $routes = [];

	public static function route($methods, $request, $action) {
		
		if (substr($request, 0, 1) !== '/') {
			die('Something went wrong (Route.Route Error)');
		}
		
		if (!is_callable($action) && strpos($action, '.') === false) {
			die('Something went wrong (Route.Target Error)');
		}
		
		Router::$routes[$request] = [
			'type' => 'route',
			'methods' => explode(',', str_replace(' ', '', $methods)),
			'action' => $action 
		];
		
	}

	public static function redirect($from, $to) {
		
		if (substr($from, 0, 1) !== '/') {
			die('Something went wrong (Route.Redirect.Route Error)');
		}
		
		if (substr($to, 0, 1) !== '/' && filter_var($to, FILTER_VALIDATE_URL) === false) {
			die('Something went wrong (Route.Redirect.Target Error)');
		}
		
		Router::$routes[$from] = [
			'type' => 'redirect',
			'target' => $to
		];
		
	}

	private static function match() {

		$uri = trim($_SERVER['REQUEST_URI']);
		$slashes = substr_count($uri, '/'); 
		$chunks = explode('/', $uri);

		foreach (Router::$routes as $route => $data) {
			if ($slashes === substr_count($route, '/')) {

				// Exact match
				if ($route == $uri) { return Router::prepare($data); }

				// Has variables
				if (strpos($route, '{') !== false) {

					$variables = [];
					$request = '';
					$target = '';

					foreach (explode('/', $route) as $key => $value) {
						if (substr($value, 0, 1) === '{' && substr($value, -1) === '}') {
							$variables[preg_replace('/\{+||\}+/', '', $value)] = $chunks[$key];
							$request .= '/';
							$target .= '/';
						} else {
							$request .= '/'.$chunks[$key];
							$target .= '/'.$value;
						}
					}

					// Exact match without variable chunks
					if ($target == $request) {
						$data['variables'] = $variables;
						return Router::prepare($data);
					}

				}

			}
		}

		/* No match found for supplied route */
		die('Something went wrong (Unknown Route)');

	}

	private static function prepare($match) {

		if ($match['type'] == 'redirect') {

			// Apply variables if available
			if (isset($match['variables'])) {
				foreach ($match['variables'] as $variable => $value) {
					$match['target'] = str_replace('{'.$variable.'}', $value, $match['target']);
				}
			}

			header('Location: '.$match['target']); 
			die();
			
		} else { // Normal route

			/* Validate method */
			if (!in_array($_SERVER['REQUEST_METHOD'], $match['methods'])) {
				die('Something went wrong (Request.Method Error)');
			}

			return $match;

		}

	}

	public static function load() {
		return Router::match();
	}
	
}

?>