<?php 

use Milkshake\Settings;

/** 
*
* Milkshake Config
* 
* Use the following format to create config variables:
* Settings::set(Name, Value);
*
**/

Settings::set('TIMEZONE', 'Europe/Copenhagen');

Settings::set('DB_HOST', 'localhost');
Settings::set('DB_USER', '');
Settings::set('DB_PASS', '');
Settings::set('DB_NAME', '');
Settings::set('DB_CHARSET', 'utf8');

?>