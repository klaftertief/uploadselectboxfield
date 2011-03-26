<?php

	require_once(TOOLKIT . '/class.ajaxpage.php');

	Class contentExtensionUploadselectboxfieldUpload extends AjaxPage{

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
			// HTTP headers for no cache etc
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
			header('Content-Type: application/json');
			
			// Settings
			$targetDir = TMP . DIRECTORY_SEPARATOR . "plupload";
			$maxFileAge = 60 * 10; // Temp file age in seconds

			// 5 minutes execution time
			@set_time_limit(5 * 60);
			// usleep(5000);

			// Get parameters
			$chunk = isset($_REQUEST["chunk"]) ? $_REQUEST["chunk"] : 0;
			$chunks = isset($_REQUEST["chunks"]) ? $_REQUEST["chunks"] : 0;
			$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';

			// Clean the fileName for security reasons
			$fileName = preg_replace('/[^\w\._]+/', '', $fileName);

			// Create target dir
			if (!file_exists($targetDir))
				@mkdir($targetDir);

			// Remove old temp files
			if (is_dir($targetDir) && ($dir = opendir($targetDir))) {
				while (($file = readdir($dir)) !== false) {
					$filePath = $targetDir . '/' . $file;

					// Remove temp files if they are older than the max age
					if (is_file($filePath) && (substr($file, -2) != 'db') && (filemtime($filePath) < time() - $maxFileAge)){
						unlink($filePath);
					}
				}

				closedir($dir);
			} else
				die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');

			// Look for the content type header
			if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
				$contentType = $_SERVER["HTTP_CONTENT_TYPE"];

			if (isset($_SERVER["CONTENT_TYPE"]))
				$contentType = $_SERVER["CONTENT_TYPE"];

			if (strpos($contentType, "multipart") !== false) {
				if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
					// Open temp file
					$out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
					if ($out) {
						// Read binary input stream and append it to temp file
						$in = fopen($_FILES['file']['tmp_name'], "rb");

						if ($in) {
							while ($buff = fread($in, 4096)){
								fwrite($out, $buff);
							}
						} else
							die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');

						fclose($out);
					} else
						die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
				} else
					die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
			} else {
				// Open temp file
				$out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
				if ($out) {
					// Read binary input stream and append it to temp file
					$in = fopen("php://input", "rb");

					if ($in) {
						while ($buff = fread($in, 4096))
							fwrite($out, $buff);
					} else
						die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');

					fclose($out);
				} else
					die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
			}

			// move file to target directory
			// TODO make target directory configurable
			rename($targetDir . DIRECTORY_SEPARATOR . $fileName, WORKSPACE . "/media/images/test/" . $_FILES['file']['name']);
			
			// Return JSON-RPC response
			die('{"jsonrpc" : "2.0", "result" : null, "id" : "id","filename":"'.$fileName.'"}');

			// echo $this->_Result;
			// exit;
		}
	}

