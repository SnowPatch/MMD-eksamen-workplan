<?php 

namespace Milkshake\Controller;

use Milkshake\System\Controller;

class MainController extends Controller {

	public function index() {

		$data = [
			'title' => 'Milkshake Framework',
		];

		return $this->render('main.index', $data);
		
    }
	
}

?>