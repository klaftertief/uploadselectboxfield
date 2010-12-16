<?php
 
	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(TOOLKIT . '/class.entrymanager.php');
	require_once(EXTENSIONS . '/uploadselectboxfield/lib/class.uploadselectboxfield.php');
	
	class contentExtensionUploadselectboxfieldGet extends AdministrationPage {
 
		public function __construct(&$parent){
			parent::__construct($parent);
		}

		public function __viewIndex() {
			// TODO dynamic path
			$items = array();
			$states = General::listStructure(DOCROOT . '/workspace/media/uploads', null, false, 'asc', DOCROOT);
			
			if (is_null($states['filelist']) || empty($states['filelist'])) $states['filelist'] = array();
			
			foreach($states['filelist'] as $handle => $v){
				$items[] = '<li class="preview" data-value="' . $v . '"><img src="' . URL . '/image/2/40/40/5/media/uploads/' . $v . '" width="40" height="40" /><a href="' . URL . '/workspace/media/uploads/' . $v . '" class="image file">' . $v . '</a></li>';
			}
						
			sort($items);
			
			$html = implode('', $items);
			
			echo $html;
			exit;
		}

	}
 
?>