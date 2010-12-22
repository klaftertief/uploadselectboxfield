<?php

	Class extension_Uploadselectboxfield extends Extension{

		public function about(){
			return array('name' => 'Field: Uploaded File Select Box',
						 'version' => '1.2',
						 'release-date' => '2010-12-22',
						 'author' => array('name' => 'Nick Dunn, Brendan Abbott, Jonas Coch',
										   'website' => 'http://nick-dunn.co.uk')
				 		);
		}

		public function uninstall(){
			// Drop related entries from stage table
			Administration::instance()->Database->query("DELETE FROM `tbl_fields_stage` WHERE `context` = 'subsectionmanager'");
		
			// Drop database tables
			Administration::instance()->Database->query("DROP TABLE IF EXISTS `tbl_fields_uploadselectbox`");
			Administration::instance()->Database->query("DROP TABLE IF EXISTS `tbl_fields_uploadselectbox_sorting`");
		}

		public function install(){
			
			// Create database field table
			$fields = Administration::instance()->Database->query(
				"CREATE TABLE IF NOT EXISTS `tbl_fields_uploadselectbox` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `field_id` int(11) unsigned NOT NULL,
				  `allow_multiple_selection` enum('yes','no') NOT NULL default 'no',
				  `destination` varchar(255) NOT NULL,
				  PRIMARY KEY  (`id`),
				  UNIQUE KEY `field_id` (`field_id`)
				) TYPE=MyISAM"
			);

			// Create database sorting table
			$sorting = Administration::instance()->Database->query(
				"CREATE TABLE IF NOT EXISTS `tbl_fields_uploadselectbox_sorting` (
				  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `entry_id` int(11) NOT NULL,
				  `field_id` int(11) NOT NULL,
				  `order` text,
				  PRIMARY KEY (`id`)
				)"
			);
		
			// Create database stage table
			$stage = Administration::instance()->Database->query(
				"CREATE TABLE IF NOT EXISTS `tbl_fields_stage` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `field_id` int(11) unsigned NOT NULL default '0',
				  `constructable` smallint(1) default '0',
				  `destructable` smallint(1) default '0',
				  `draggable` smallint(1) default '0',
				  `droppable` smallint(1) default '0',
				  `searchable` smallint(1) default '0',
				  `context` varchar(255) default NULL,
				  PRIMARY KEY  (`id`)
				) TYPE=MyISAM;"
			);
		
		// Return status
		if($fields && $sorting && $stage) {
			return true;
		}
		else {
			return false;
		}

		}

		public function update($previousVersion){
			if (version_compare($previousVersion, '1.2', '<')) {
				
				// Install missing tables
				$this->install();
				
				// Update existing field data tables
				$fields = array();
				// Find fields (field_id and destination)
				$fields = Administration::instance()->Database->fetch(
					"SELECT field_id, destination
					FROM tbl_fields_uploadselectbox"
				);
				
				if (!empty($fields)) {
					foreach ($fields as $field) {
						$destination = str_replace('/workspace', '', $field['destination']) . '/';
						
						// Add size, mimetype and meta columns
						Administration::instance()->Database->query(
							"ALTER TABLE `tbl_entries_data_{$field['field_id']}`
							ADD COLUMN `size` int(11) unsigned NULL,
							ADD COLUMN `mimetype` varchar(50) default NULL,
							ADD COLUMN `meta` varchar(255) default NULL,
							ADD KEY `file` (`file`),
							ADD KEY `mimetype` (`mimetype`)"
						);
						
						// prepend content of file column with field destination
						Administration::instance()->Database->query(
							"UPDATE `tbl_entries_data_{$field['field_id']}`
							SET `file` = CONCAT('{$destination}', `file`)"
						);
					}
				}
				
			}
		}

		public function createFileList($destination, $selectedFiles = array()){
			$html = array();
			$options = array();
			$states = General::listStructure(DOCROOT . $destination, null, false, 'asc', DOCROOT);
			$path = str_replace('/workspace', '', $destination);
			
			if (is_null($states['filelist']) || empty($states['filelist'])) $states['filelist'] = array();
			
			foreach($states['filelist'] as $handle => $v){
				$selected = empty($selectedFiles) || in_array($path . '/' . $v, $selectedFiles);
				$mimetype = $this->getMimetype(WORKSPACE . $path . '/' . $v);
				
				// Build options array
				$options[] = array($path . '/' . General::sanitize($v), $selected, $v);
				
				// Build list items array
				if ($selected) {
					// image
					if(strpos($mimetype, 'image') !== false) {
						$html[] = '<li class="preview" data-value="' . $path . '/' . $v . '"><img src="' . URL . '/image/2/40/40/5' . $path . '/' . $v . '" width="40" height="40" /><a href="' . URL . $destination . '/' . $v . '" class="image file">' . $v . '</a></li>';
					}
					// file
					else {
						$extension = $this->getFileExtension($v);
						$html[] = '<li class="preview" data-value="' . $path . '/' . $v . '"><strong class="file">' . $extension . '</strong><a href="' . URL . $destination . '/' . $v . '" class="file">' . $v . '</a></li>';
					}
				}
			}
			
			sort($html);
			sort($options);
			
			$html = implode('', $html);
			
			return array(
				'html' => $html,
				'options' => $options
			);
		}

		public static function getMetaInfo($file, $type){

			$imageMimeTypes = array(
				'image/gif',
				'image/jpg',
				'image/jpeg',
				'image/pjpeg',
				'image/png',
			);
			
			$meta = array();
			
			$meta['creation'] = DateTimeObj::get('c', filemtime($file));
			
			if(General::in_iarray($type, $imageMimeTypes) && $array = @getimagesize($file)){
				$meta['width']    = $array[0];
				$meta['height']   = $array[1];
			}
			
			return $meta;
			
		}

		public static function getFileExtension($file){
			$parts = explode('.', basename($file));
			return array_pop($parts);
		}

		public function getMimetype($file) {
			
			if (!(function_exists('finfo_open') && is_readable($file) && $finfo = new finfo(FILEINFO_MIME))) {
				$ct['htm'] = 'text/html';
				$ct['html'] = 'text/html';
				$ct['txt'] = 'text/plain';
				$ct['asc'] = 'text/plain';
				$ct['bmp'] = 'image/bmp';
				$ct['gif'] = 'image/gif';
				$ct['jpeg'] = 'image/jpeg';
				$ct['jpg'] = 'image/jpeg';
				$ct['jpe'] = 'image/jpeg';
				$ct['png'] = 'image/png';
				$ct['ico'] = 'image/vnd.microsoft.icon';
				$ct['mpeg'] = 'video/mpeg';
				$ct['mpg'] = 'video/mpeg';
				$ct['mpe'] = 'video/mpeg';
				$ct['qt'] = 'video/quicktime';
				$ct['mov'] = 'video/quicktime';
				$ct['avi']  = 'video/x-msvideo';
				$ct['wmv'] = 'video/x-ms-wmv';
				$ct['mp2'] = 'audio/mpeg';
				$ct['mp3'] = 'audio/mpeg';
				$ct['rm'] = 'audio/x-pn-realaudio';
				$ct['ram'] = 'audio/x-pn-realaudio';
				$ct['rpm'] = 'audio/x-pn-realaudio-plugin';
				$ct['ra'] = 'audio/x-realaudio';
				$ct['wav'] = 'audio/x-wav';
				$ct['css'] = 'text/css';
				$ct['zip'] = 'application/zip';
				$ct['pdf'] = 'application/pdf';
				$ct['doc'] = 'application/msword';
				$ct['bin'] = 'application/octet-stream';
				$ct['exe'] = 'application/octet-stream';
				$ct['class']= 'application/octet-stream';
				$ct['dll'] = 'application/octet-stream';
				$ct['xls'] = 'application/vnd.ms-excel';
				$ct['ppt'] = 'application/vnd.ms-powerpoint';
				$ct['wbxml']= 'application/vnd.wap.wbxml';
				$ct['wmlc'] = 'application/vnd.wap.wmlc';
				$ct['wmlsc']= 'application/vnd.wap.wmlscriptc';
				$ct['dvi'] = 'application/x-dvi';
				$ct['spl'] = 'application/x-futuresplash';
				$ct['gtar'] = 'application/x-gtar';
				$ct['gzip'] = 'application/x-gzip';
				$ct['js'] = 'application/x-javascript';
				$ct['swf'] = 'application/x-shockwave-flash';
				$ct['tar'] = 'application/x-tar';
				$ct['xhtml']= 'application/xhtml+xml';
				$ct['au'] = 'audio/basic';
				$ct['snd'] = 'audio/basic';
				$ct['midi'] = 'audio/midi';
				$ct['mid'] = 'audio/midi';
				$ct['m3u'] = 'audio/x-mpegurl';
				$ct['tiff'] = 'image/tiff';
				$ct['tif'] = 'image/tiff';
				$ct['rtf'] = 'text/rtf';
				$ct['wml'] = 'text/vnd.wap.wml';
				$ct['wmls'] = 'text/vnd.wap.wmlscript';
				$ct['xsl'] = 'text/xml';
				$ct['xml'] = 'text/xml';

				$extension = $this->getFileExtension($value);
				if (!$type = $ct[strtolower($extension)]) {
					$type = 'unknown';
				}
			} else {
				$type = $finfo->file($file);
				// remove charset (added as of PHP 5.3)
				if (false !== $pos = strpos($type, ';')) {
					$type = substr($type, 0, $pos);
				}
			}
			
			return $type;
		}


	}