<?php

	require_once(TOOLKIT . '/class.ajaxpage.php');

	Class contentExtensionUploadselectboxfieldGetfilelist extends AjaxPage{

		public function handleFailedAuthorisation(){
			$this->_status = self::STATUS_UNAUTHORISED;
			$this->_Result = json_encode(array('status' => __('You are not authorised to access this page.')));
		}

		public function view(){
			$directory = WORKSPACE . $_GET['destination'];
			$filter = '/' . $_GET['filter'] . '/';

			$states = General::listStructure($directory, $filter, false, 'asc', $directory);

			$this->_Result = json_encode($states['filelist']);
		}

		public function generate(){
			header('Content-Type: application/json');
			echo $this->_Result;
			exit;
		}

	}

