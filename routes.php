<?php 

use Milkshake\System\Router;

/** 
*
* Milkshake Router
*
**/

// Main routes
Router::route('GET', '/', 'MainController.index');
Router::route('GET, POST', '/register', 'AuthController.register');

// Panel Routes
Router::redirect('/{client}', '/{client}/');
Router::route('GET', '/{client}/', 'PanelController.index');
Router::route('GET', '/{client}/plan', 'PanelController.plan');
Router::route('GET', '/{client}/setup', 'PanelController.setup');

// Auth routes
Router::route('GET, POST', '/{client}/login', 'AuthController.login');
Router::route('GET', '/{client}/logout', 'AuthController.logout');

?>