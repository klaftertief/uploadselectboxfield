<?php

	require_once(TOOLKIT . '/class.ajaxpage.php');

	Class contentExtensionUploadselectboxfieldGetfilelist extends AjaxPage{

		public function handleFailedAuthorisation(){
			$this->_status = self::STATUS_UNAUTHORISED;
			$this->_Result = json_encode(array('status' => __('You are not authorised to access this page.')));
		}

		public function view(){
			$directory = $_GET['destination'] ? $_GET['destination'] : '/workspace';

			$states = General::listStructure(DOCROOT . $directory, null, false, 'asc', DOCROOT);

			$this->_Result = json_encode($states);
		}

		public function generate(){
			header('Content-Type: application/json');
			echo $this->_Result;
			exit;
		}

	}

