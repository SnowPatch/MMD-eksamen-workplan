<?php

require_once 'system/Loader.php';

use Milkshake\System\Loader;
use Milkshake\System\Router;
use Milkshake\Settings;
use Milkshake\Controller;

/* Load dependencies */
Loader::init();

/* Set timezone */
$timezone = Settings::get('TIMEZONE') ?? 'Europe/Berlin';
date_default_timezone_set($timezone);

/* Route matching */
$target = Router::load($_SERVER['REQUEST_URI']);
$data = (isset($target['variables'])) ? $target['variables'] : NULL;

if (is_callable($target['action'])) {

	/* Execute route method function */
	$target['action']($data);

} else {

	/* Prepare controller.method call */
	$path = explode(".", $target['action']);
	$class = 'Milkshake\Controller\\'.$path[0];
	$method = $path[1];

	/* Return result */
	echo (new $class())->$method($data);

}

?>
