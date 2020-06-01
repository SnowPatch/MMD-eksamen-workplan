<?php 

namespace Milkshake\Model;

use Milkshake\System\Model;
use PHPMailer\PHPMailer;
use PHPMailer\SMTP;

class Auth extends Model {
    
    public function response($status, $data='') {
		return json_encode(['status' => $status, 'data' => $data]);
	}

	public function token($length=256) {
	
		$token = '';
		$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ#&%@.+';
		
		for ($i = 0; $i < $length; $i++) { 
			$token .= $chars[rand(0, strlen($chars) - 1)]; 
		} 
	
		return $token;
	
	}

	public function smtp($to, $subject, $message, $alt='')  {  

        $mail = new \PHPMailer;

        $mail->isSMTP();
        $mail->Host = ''; 
        $mail->SMTPAuth = true;
        $mail->Username = '';
        $mail->password = '';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
    
        $mail->setFrom('', 'Workplan');
        $mail->addAddress($to); 
        $mail->addReplyTo('', 'Kundeservice');
    
        $mail->isHTML(true); 
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = $alt;
    
        if($mail->send()) {
            return true;
        } else {
            return $mail->ErrorInfo;
        }

	}  

	public function createClient($cvr, $name, $uri, $email) {
	
		$client = $this->query('INSERT INTO clients (cvr, name, uri) VALUES (?, ?, ?)', [$cvr, $name, $uri]);
		if ($client === false) { return false; }

		$password = $this->token(16);
		$encrypted = password_hash($password, PASSWORD_DEFAULT);

		$user = $this->query('INSERT INTO users (client, name, email, password, admin) VALUES (?, "Administrator", ?, ?, 1)', [$this->getLastId(), $email, $encrypted]);
		if ($user === false) { return false; }

        $mail = $this->smtp($email, 'Din konto er oprettet', 'Kodeord: <br>'.$password);
        
		return ($mail === true) ? true : false;
	
    }
    
    public function getClient($row, $input) {

        $result = $this->query('SELECT * FROM clients WHERE '.$this->escape($row).' = ?', [$input]);
        return (count($result) > 0) ? $result : false;
        
    }
    
    public function getUser($row, $input) {

        $result = $this->query('SELECT * FROM users WHERE '.$this->escape($row).' = ?', [$input]);
        return (count($result) > 0) ? $result : false;
        
    }


    /* Login stuff */
    
    public function validate($type, $value, $minLength=3) {
		
		$minLength = (int)$minLength;
		
		switch ($type) {
			case "text":
				return (strlen($value) >= $minLength) ? true : false;
				break;
			case "int":
				return (is_int($value)) ? true : false;
				break;
			case "float":
				return (is_float($value)) ? true : false;
				break;
			case "email":
				return (filter_var($value, FILTER_VALIDATE_EMAIL)) ? true : false;
				break;
			case "bool":
				return ($value === true || $value === false) ? true : false;
				break;
			default:
				die('Something went wrong (User.Validate.Type Error)');
		}
	
	}
	
	public function setKey($netkey, $cookie=false) {
		
		if($cookie === true) {
			$timespan = time() + 5 * 24 * 60 * 60; // 5 days cookie duration
			setcookie('netkey', $netkey, $timespan, '/');
		} else {
			$_SESSION['netkey'] = $netkey;
		}
		
		return true;
	
	}
	
	public function requireLogin($client) {
        
        if (!$this->validate('int', $client)) { 
			return false;
		}

		if (!isset($_COOKIE['netkey']) && !isset($_SESSION['netkey'])) { 
			return false;
		}
		
		$key = (isset($_COOKIE['netkey'])) ? $_COOKIE['netkey'] : $_SESSION['netkey'];
		$cookie = (isset($_COOKIE['netkey'])) ? true : false;
		
		$sql = "SELECT user, updated FROM sessions WHERE netkey = ? AND client = ?";
		$result = $this->query($sql, [$key, $client]);
		
		if (count($result) !== 1) { 
			return false;
		}
		
		$user = $result[0]['user'];
		$newkey = $this->token();
		
		$sql = 'UPDATE sessions SET netkey = ?, updated = '.time().' WHERE netkey = ?';
		if (($result = $this->query($sql, [$newkey, $key])) === false) {
			return false;
		}
		
		$setKey = $this->setKey($newkey, $cookie);
		
		return true;
		
	}
	
	public function login($client, $email, $password, $cookie) {

        if (!$this->validate('int', $client) || 
            !$this->validate('email', $email) || 
            !$this->validate('text', $password) || 
            !$this->validate('bool', $cookie)) { 
			return false;
		}
		
		$sql = 'SELECT u.id, u.password FROM users u LEFT JOIN clients c ON (u.client = c.id) WHERE u.email = ? AND u.deleted = 0 AND c.deleted = 0';
		$result = $this->query($sql, [$email]);
		
		if (count($result) !== 1) { 
			return false;
		}
		
		$stored_id = $result[0]['id'];
		$stored_password = $result[0]['password'];
		
		if (!password_verify($password, $stored_password)) { 
			return false;
		}
		
		$netkey = $this->token();
		
		$sql = "INSERT INTO sessions (netkey, user, client) VALUES (?, ?, ?)";
		$result = $this->query($sql, [$netkey, $stored_id, $client]);
		
		$setKey = $this->setKey($netkey, $cookie);
		
		return true;
		
	}
	
	public function logout() {
		
		if(isset($_COOKIE['netkey'])) {
			setcookie('netkey', FALSE, -1, '/');
		}
		session_destroy();
		
		return true; 
		
	}
	
}

?>