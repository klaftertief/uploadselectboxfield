<?php
 
	require_once(TOOLKIT . '/class.administrationpage.php');
	
	class contentExtensionUploadselectboxfieldGet extends AdministrationPage {
		protected $_driver = null;

		public function __construct(&$parent){
			parent::__construct($parent);
			$this->_driver = $this->_Parent->ExtensionManager->create('uploadselectboxfield');
		}

		public function __viewIndex() {
			$content = $this->_driver->createFileList($_GET['destination']);
			
			echo $content['html'];
			exit;
		}

	}
 
?>