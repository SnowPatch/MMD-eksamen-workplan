<?php 

use Milkshake\System\Router;

/** 
*
* Milkshake Assetbuilder
* 
* @build('css', 'css/main, css/grid', true)
*
**/

Router::route('GET', '/assetbuiler/{type}/{files}', function($param) {

    ob_start();

    $type = strtolower($param['type']);
    $files = array_unique(explode(',', rawurldecode(explode('?', $param['files'])[0])));
    $filetimes = [];

    header('X-Content-Type-Options: nosniff');

    switch ($type) {
        case 'css':
            header('Content-Type: text/css');
            break;
        case 'js':
            header('Content-Type: application/javascript');
            break;
        default:
            die();
    }

    foreach ($files as $file) {

        $path = 'assets/'.str_replace(':', '/', trim($file)).'.'.$type;

        if (is_file($path) && is_readable($path)) {
            $filetimes[] = filemtime($path);
            readfile($path);
        } 

    }

    header('Last-Modified: '.date('D, d M Y H:i:s T', max($filetimes)));

    echo ob_get_clean();
    
});

?>