<?php 

namespace Milkshake\Controller;

use Milkshake\System\Controller;

@session_start();

class AuthController extends Controller {

	public function login($p) {

        $auth = $this->model('Auth');

        if (!($client = $auth->getClient('uri', $p['client']))) {
            http_response_code(404);
            die(); 
        }

        // Login form submitted
        if (isset($_POST['login'])) {

            $login = $auth->login($client[0]['id'], $_POST['email'], $_POST['password'], isset($_POST["cookie"])); 
            if ($login) {
                header('Location: /'.$p['client'].'/');
                die();
            }

        }

        $data = ['client' => $client[0]['name']];
		
		$user = $auth->requireLogin($client[0]['id']);
		if ($user) { 
            header('Location: /'.$p['client'].'/');
            die();
        }
        
        return $this->render('auth.login', $data);

    }

    public function register() {

        $data = json_decode(file_get_contents('php://input'));

        // XHR request
        if (isset($data->request)) {

            $auth = $this->model('Auth');

            // CVR Lookup
            if ($data->request == 'cvr-lookup') {

                if ($auth->getClient('cvr', (int)$data->cvr)) {
                    die($auth->response(false, 'En vagtplan for dette CVR nummer findes allerede'));
                }
                
                $get = file_get_contents('https://cvrapi.dk/api?country=dk&vat='.(int)$data->cvr, false, stream_context_create([
                    'http' => ['method' => "GET", 'header' => "User-Agent: Workplan.dk - Company creation - CVR Lookup\r\n"]
                ]));
                $cvr = json_decode($get);

                if (isset($cvr->name, $cvr->email)) {
                    die($auth->response(true, [
                        'cvr' => (int)$data->cvr, 
                        'name' => $cvr->name,
                        'email' => $cvr->email,
                    ]));
                } else {
                    die($auth->response(false, 'Ugyldigt CVR nummer indtastet'));
                }
                
            }

            // Registration
            if ($data->request == 'registration') {
                
                if (!isset($data->cvr) || !is_numeric($data->cvr)) {
                    die($auth->response(false, 'Ugyldigt CVR nummer indtastet'));
                }

                if ($auth->getClient('cvr', (int)$data->cvr)) {
                    die($auth->response(false, 'En vagtplan med dette CVR nummer findes allerede'));
                }

                if (!isset($data->name)) {
                    die($auth->response(false, 'Hovsa, der skete en fejl. Prøv igen senere'));
                }

                if (!isset($data->uri) || strlen($data->uri) <= 3) {
                    die($auth->response(false, 'Ugyldigt link indtastet'));
                }

                if (!isset($data->email) || !filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
                    die($auth->response(false, 'Ugyldig email indtastet'));
                }

                if ($auth->getUser('email', $data->email)) {
                    die($auth->response(false, 'En vagtplan med denne email findes allerede'));
                }

                $create = $auth->createClient($data->cvr, $data->name, $data->uri, $data->email);
                if ($create === true) {
                    die($auth->response(true));
                } else {
                    die($auth->response(false));
                }

            } else {
                die();
            }

        }

		return $this->render('auth.register');
        
    }

    public function logout($p) {

        $auth = $this->model('Auth');

        $logout = $auth->logout();
        
		header('Location: /'.$p['client'].'/login');
        
    }
	
}

?>