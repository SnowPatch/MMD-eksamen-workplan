<?php 

namespace Milkshake\System; 

use Milkshake\Settings;

class Model {
	
	private $db;
	private $charset;
	
	public function __construct() {
		
		$this->charset = Settings::get('DB_CHARSET') ?? "utf8";
		
		if (strlen(Settings::get('DB_HOST')) > 0 && strlen(Settings::get('DB_USER')) > 0 && strlen(Settings::get('DB_NAME')) > 0) {
			
			$this->db = new \mysqli(
				Settings::get('DB_HOST'), 
				Settings::get('DB_USER'), 
				Settings::get('DB_PASS'), 
				Settings::get('DB_NAME')
			);

			if ($this->db->connect_error) {
				die('Something went wrong (DB.Connect Error)'); 
			} 
				
			$this->db->set_charset($this->charset);
			$this->db->query("SET SQL_MODE = ''");
			
		}
		
	}
	
	private function bind($sql, $stmt, $params) {
		
		/* Count placeholders */
        $numPh = substr_count($sql, '?');
		
		/* Equal amount of params */
		if ($params && count($params) === $numPh) {
			
			$types = '';
			
			foreach ($params as $val) {
				if (is_int($val)) {
					$types .= 'i'; /* Integer */
				} 
				elseif (is_float($val)) {
					$types .= 'd'; /* Double */
				} 
				elseif (is_string($val)) {
					$types .= 's'; /* String */
				} 
				else {
					$types .= 'b'; /* BLOB or unknown */
				}
			}
			
			/* Bind all params */
			if ($stmt->bind_param($types, ...$params)) {
				return true;
			} else {
				return false;
			}
			
		} else {
			/* No parameters */
			return true;
		}
		
	}
	
	private function execute($sql, $params, $return = false) {
		
		/* Prepare */
		if (!$stmt = $this->db->prepare($sql)) { 
			die('Something went wrong (DB.Select.Prepare Error)'); 
        }
		
		// Bind params if set
		if ($params) {
			if (!$this->bind($sql, $stmt, $params)) { 
				die('Something went wrong (DB.Select.Bind Error)'); 
			} 
		}
		
		/* Execute */
		if (!$stmt->execute()) { 
			die('Something went wrong (DB.Select.Exec Error)'.$stmt->error); // $stmt->error
		} 

		$result = ($return) ? $stmt->get_result() : true;
		
		$stmt->close();
		
		return $result;
		
	}
	
	public function query($sql, $params = false) {
		
		$type = explode(' ', $sql);
		$type = strtolower($type[0]);
		$return = ($type === 'select') ? true : false;
		
		$result = $this->execute($sql, $params, $return);
		
		if ($return) {

			$array = [];
			while ($row = $result->fetch_assoc()) {
			  array_push($array, $row);
			}
			return $array;
			
		} else {
			
			return $result;
			
		}
		
	}
	
	public function escape($value) {
		return $this->db->real_escape_string($value);
	}

	public function getLastId() {
		return $this->db->insert_id;
    }

	public function __destruct() {
		if ($this->db) {
			$this->db->close();
		}
	}

}

?>