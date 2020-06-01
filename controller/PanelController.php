<?php 

namespace Milkshake\Controller;

use Milkshake\System\Controller;

@session_start();

class PanelController extends Controller {

	public function index($p) {
        
        $auth = $this->model('Auth');

        if (!($client = $auth->getClient('uri', $p['client']))) {
            http_response_code(404);
            die(); 
        }
		
		$user = $auth->requireLogin($client[0]['id']);
		if (!$user) { 
            header('Location: /'.$p['client'].'/login');
            die();
        }

        $data = ['client' => $client[0]];

        return $this->render('panel.index', $data);

    }

    public function plan($p) {
        
        $auth = $this->model('Auth');

        if (!($client = $auth->getClient('uri', $p['client']))) {
            http_response_code(404);
            die(); 
        }
		
		$user = $auth->requireLogin($client[0]['id']);
		if (!$user) { 
            header('Location: /'.$p['client'].'/login');
            die();
        }

        $data = ['client' => $client[0]];

        return $this->render('panel.plan', $data);

    }

    public function setup($p) {
        
        $auth = $this->model('Auth');

        if (!($client = $auth->getClient('uri', $p['client']))) {
            http_response_code(404);
            die(); 
        }
		
		$user = $auth->requireLogin($client[0]['id']);
		if (!$user) { 
            header('Location: /'.$p['client'].'/login');
            die();
        }

        $data = ['client' => $client[0]];

        return $this->render('panel.setup', $data);

    }
	
}

?>