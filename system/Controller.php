<?php 

namespace Milkshake\System; 

class Controller {
	
	private $data;
	private $content;
	private $chain = [];
	private $result;
	
	private function view($path) {
		
		/* Log path for loop prevention */
		$this->chain[] = $path;
		
		$path = str_replace('.', '/', $path);
		$file = 'view/' . $path . '.html';
		
		if (!is_file($file)) {
			die('Something went wrong (Template file not found)');
		}
		if (!is_readable($file)) {
			die('Something went wrong (Template file not readable)');
		}
		
		return file_get_contents($file);
		
	}
	
	private function block($name, $content) {
		
		$result = "";
		
		/* Find block with string (autoescape) */
		if (empty($result)) {
			preg_match('/@block[\s]*\(\''.$name.'\'\,[\s]*\'(.+)\'\)/', $content, $blockStr);
			$result = (!empty($blockStr[0])) ? '{{ htmlspecialchars(\''.$blockStr[1].'\'); }}' : "";
		}
		
		/* Find block with variable (autoescape) */
		if (empty($result)) {
			preg_match('/@block[\s]*\(\''.$name.'\'\,[\s]*\$([a-zA-Z0-9_\'\[\]]+)\)/', $content, $blockVar);
			$result = (!empty($blockVar[0])) ? '@echo $'.$blockVar[1] : "";
		}
		
		/* Find regular block */
		if (empty($result)) {
			preg_match('/@block[\s]*\(\''.$name.'\'\)(.*?)@endblock/is', $content, $block);
			$result = (!empty($block[0])) ? $block[1] : "";
		}
		
		return $result;
		
	}
	
	private function tags($name, $content, $limit) {
		
		$result['status'] = false;
		
		preg_match_all('/@'.$name.'[\s]*\(\'([a-zA-Z0-9_\/\-\.]+)\'\)/i', $content, $tags);
		
		if (!empty($tags[0])) {
			
			if (is_int($limit) && count($tags[0]) > $limit) { 
				die('Too many @'.$name.'. They are limited to '.$limit.' per file'); 
			}
			
			$result['status'] = true;
			$result['tag'] = $tags[0];
			$result['target'] = $tags[1];
			
		} 
			
		return $result;
		
	}
	
	private function replace($tag, $limit, $content, $from) {
		
		/* Find all @leap */
		$replace = $this->tags($tag, $content, $limit);
		
		/* If tag was found */
		if ($replace['status']) {
			
			foreach($replace['target'] as $key => $name) {
					
				/* Get block content with same name as current insert */
				$block = ($tag == 'leap') ? '@block(\''.$name.'\') @insert(\''.$name.'\') @endblock' : $this->block($name, $from);
				
				// str_replace @insert ($inserts[0][$key]) with $block contents 
				$content = str_replace($replace['tag'][$key], $block, $content);
				
			}
			
		} 
			
		return $content; 
		
	}
	
	private function extend($content) {
		
		/* Find all @extends */
		$extends = $this->tags('extends', $content, 1);
		
		/* If @extends was found */
		if ($extends['status']) {
			
			/* Prevent loops by checking extend path against $this->chain */
			if (in_array($extends['target'][0], $this->chain)) {
				die('Selected view has already been used');
			}
			
			/* Get extended file */
			$extended = $this->view($extends['target'][0]);
			
			/* @leap block passthrough */
			$extended = $this->replace('leap', false, $extended, $content);
			
			/* Insert blocks */
			$extended = $this->replace('insert', false, $extended, $content);
			
			/* Check for more extends until none are found, then content is saved */
			$check = $this->extend($extended);
			
			return true;
			
		} else {
			
			$this->content = $content;
			return true;
		
		}
		
	}
	
	private function compile() {
		
		/* Convert data array into local variables */
		if ($this->data) {
			extract($this->data, EXTR_SKIP);
		}
		
		$this->result = preg_replace([
			
			/* Remove raw PHP */
			'/<\?php[\s\S]*\?>/i',
			'/<\?/',
			
			/* Echo raw */
			'/{{/',
			'/}}/', 
			
			/* Echo escaped */
			'/@echo \$([a-zA-Z0-9_\'\[\]]+)/i',
			'/@echo[\s]*\((.+)\)/i',
			
			/* PHP code blocks */
			'/\[\[/',
			'/\]\]/',
			
			/* End statement */
			'/@end(?!\S)/i', 
			
			/* IF/ELSE */
			'/@if[\s]*\((.+)\)/i',
			'/@elseif[\s]*\((.+)\)/i',
			'/@else/i',
			
			/* Isset */
			'/@isset \$([a-zA-Z0-9_\'\[\]]+)/i',
			'/@isset[\s]*\((.+)\)/i',
			
			/* Empty */
			'/@empty \$([a-zA-Z0-9_\'\[\]]+)/i',
			'/@empty[\s]*\((.+)\)/i',
			
			/* For loop */
			'/@for[\s]*\((.+)\)/i',
			
			/* Foreach loop */
			'/@foreach[\s]*\((.+)\)/i',
			
			/* While loop */
			'/@while[\s]*\((.+)\)/i',
			
			/* Date */
			'/@date[\s]*\(\'(.+)\'\)/i',
			
            /* Assets */
            '/@asset \$([a-zA-Z0-9_\'\[\]]+)/i',
			'/@asset[\s]*\(\'([a-zA-Z0-9_\/\-\. ]+)\'\,[\s]*true\)/i',
			'/@asset[\s]*\(\'([a-zA-Z0-9_\/\-\. ]+)\'\,[\s]*false\)/i',
			'/@asset[\s]*\(\'([a-zA-Z0-9_\/\-\. ]+)\'\)/i',
			
			/* Assetbuilder */
			'/@build[\s]*\(\'([a-zA-Z]+)\'\,[\s]\'([a-zA-Z0-9_\/\-\., ]+)\'\,[\s]*true\)/i',
			'/@build[\s]*\(\'([a-zA-Z]+)\'\,[\s]\'([a-zA-Z0-9_\/\-\., ]+)\'\,[\s]*false\)/i',
            '/@build[\s]*\(\'([a-zA-Z]+)\'\,[\s]\'([a-zA-Z0-9_\/\-\., ]+)\'\)/i',
            
            /* Request URI */
            '/@self(?![\w.-])/i',  
            
		], [
				
			/* Remove raw PHP */
			'',
			'',
			
			/* Echo raw */
			'<?php echo ',
			'; ?>',
			
			/* Echo escaped */
			'<?php echo htmlspecialchars(\$$1); ?>',
			'<?php echo htmlspecialchars($1); ?>',
			
			/* PHP code blocks */
			'<?php ',
			' ?>',
			
			/* End statement */
			'<?php } ?>',
			
			/* IF/ELSE */
			'<?php if ($1) { ?>',
			'<?php } elseif ($1) { ?>',
			'<?php } else { ?>',
			
			/* Isset */
			'<?php if (isset(\$$1)) { ?>',
			'<?php if (isset($1)) { ?>',
			
			/* Empty */
			'<?php if (empty(\$$1)) { ?>',
			'<?php if (empty($1)) { ?>',
			
			/* For loop */
			'<?php for ($1) { ?>',
			
			/* Foreach loop */
			'<?php foreach ($1) { ?>',
			
			/* While loop */
			'<?php while ($1) { ?>',
			
			/* Date */
			'<?php echo date(\'$1\'); ?>',
			
            /* Assets */
            '/assets/<?php echo htmlspecialchars(\$$1); ?>',
			'/assets/$1?v=<?php print filemtime(\'assets/$1\'); ?>',
			'/assets/$1',
			'/assets/$1',
			
			/* Assetbuilder */
			'/assetbuilder/$1/<?php echo rawurlencode(str_replace("/", ":", "$2")); ?>?v=<?php $filetimes = []; foreach (array_unique(explode(",", "$2")) as $file) { $filetimes[] = filemtime("assets/".trim($file).".$1"); } echo max($filetimes); ?>', 
			'/assetbuilder/$1/<?php echo rawurlencode(str_replace("/", ":", "$2")); ?>', 
			'/assetbuilder/$1/<?php echo rawurlencode(str_replace("/", ":", "$2")); ?>', 
            
            /* Request URI */
            '<?php echo $_SERVER[\'REQUEST_URI\']; ?>',
				
		], $this->content);
		
		/* Execute PHP with buffer */
		ob_start();
		eval('?>' . $this->result);
		$this->result = ob_get_clean();
		
		return true;
		
	}
	
	public function render($path, $data = false) {
		
		$this->data = $data;
		$content = $this->view($path);
		
		if ($this->extend($content) !== true) {
			// Extend error
			die('Extend failed');
		}
		
		if ($this->compile()  !== true) {
			// Compile error
			die('Compile failed');
		}

		return $this->result;
		
	}
	
	public function model($name) {
		
		$model = '\Milkshake\Model\\'.$name;
		return new $model;
		
	}

}

?>