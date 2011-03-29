<?php

	require_once(TOOLKIT . '/fields/field.select.php');

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	if(!class_exists('Stage')) {
		require_once(EXTENSIONS . '/subsectionmanager/lib/stage/class.stage.php');
	}

	Class FieldUploadselectbox extends Field {

		function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = 'Upload Select Box';
			$this->_required = true;
			
			$this->set('show_column', 'no');
			$this->set('location', 'sidebar');
			$this->set('required', 'no');
		}

		function canToggle(){
			return ($this->get('allow_multiple_selection') == 'yes' ? false : true);
		}

		function allowDatasourceParamOutput(){
			## Grouping follows the same rule as toggling.
			return $this->canToggle();
		}

		function canFilter(){
			return true;
		}

		public function canImport(){
			return false;
		}

		function canPrePopulate(){
			return true;
		}

		function isSortable(){
			return false;
		}

		public function appendFormattedElement(&$wrapper, $data, $encode = false) {

			if (!is_array($data) or empty($data)) return;

			if (!is_array($data['file'])) {
				if($data['file'] == NULL) return;
				$data = array(
					'file' => array($data['file'])
				);
			}

			$item = new XMLElement($this->get('element_name'));

			$path = DOCROOT . $this->get('destination');

			$item->setAttributeArray(array(
			 	'path' => str_replace(WORKSPACE,'', $path)
			));

			foreach($data['file'] as $index => $file) {
				$item->appendChild(new XMLElement(
					'item', General::sanitize($file), array(
						'size' => General::formatFilesize(filesize($path . '/' . $file)),
					)
				));
			}

			$wrapper->appendChild($item);
		}

		function displaySettingsPanel(&$wrapper, $errors=NULL){

			Field::displaySettingsPanel($wrapper, $errors);

			$div = new XMLElement('div', NULL, array('class' => 'group'));

			## Destination Folder
			$ignore = array(
				'/workspace/events',
				'/workspace/data-sources',
				'/workspace/text-formatters',
				'/workspace/pages',
				'/workspace/utilities'
			);
			$directories = General::listDirStructure(WORKSPACE, null, true, DOCROOT, $ignore);

			$label = Widget::Label(__('Destination Directory'));

			$options = array();
			$options[] = array('/workspace', false, '/workspace');
			if(!empty($directories) && is_array($directories)){
				foreach($directories as $d) {
					$d = '/' . trim($d, '/');
					if(!in_array($d, $ignore)) $options[] = array($d, ($this->get('destination') == $d), $d);
				}
			}

			$label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][destination]', $options));

			if(isset($errors['destination'])) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $errors['destination']));
			else $wrapper->appendChild($label);

			// Setting: allow subdirectories
			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][allow_subdirectories]', 'yes', 'checkbox');
			if($this->get('allow_subdirectories') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue(__('%s Allow subdirectories', array($input->generate())));
			$label->appendChild(new XMLElement('i', __('This will add a dropdown to select subdirectories')));
			$wrapper->appendChild($label);

			$this->buildValidationSelect($wrapper, $this->get('validator'), 'fields['.$this->get('sortorder').'][validator]', 'upload');

			// Behaviour
			$fieldset = Stage::displaySettings(
				$this->get('id'), 
				$this->get('sortorder'), 
				__('Behaviour')
			);

			// Setting: allow multiple
			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][allow_multiple_selection]', 'yes', 'checkbox');
			if($this->get('allow_multiple_selection') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue(__('%s Allow selection of multiple options', array($input->generate())));
			$label->appendChild(new XMLElement('i', __('This will switch between single and multiple item lists')));
			$div = $fieldset->getChildren();
			$div[0]->appendChild($label);

			// Append behaviour settings
			$wrapper->appendChild($fieldset);

			// General
			$fieldset = new XMLElement('fieldset');
			$div = new XMLElement('div', NULL, array('class' => 'compact'));
			$this->appendRequiredCheckbox($div);
			$this->appendShowColumnCheckbox($div);
			$fieldset->appendChild($div);
			$wrapper->appendChild($fieldset);

		}

		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/uploadselectboxfield/lib/plupload/js/plupload.full.min.js', 99, false);
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/uploadselectboxfield/lib/plupload/js/jquery.plupload.queue.min.js', 100, false);
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/uploadselectboxfield/lib/stage/stage.publish.js', 101, false);
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/uploadselectboxfield/assets/uploadselectboxfield.publish.js', 102, false);

			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/uploadselectboxfield/lib/plupload/examples/css/plupload.queue.css', 'screen', 103, false);
			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/uploadselectboxfield/lib/stage/stage.publish.css', 'screen', 103, false);
			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/uploadselectboxfield/assets/uploadselectboxfield.publish.css', 'screen', 104, false);

			if(!is_array($data['file'])) $data['file'] = array($data['file']);

			$fieldname = 'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix;
			if($this->get('allow_multiple_selection') == 'yes') $fieldname .= '[]';

			$destination = str_replace('/workspace', '', $this->get('destination'));
			$options = array();

			if ($this->get('required') != 'yes') $options[] = array(NULL, false, NULL);

			$states = General::listStructure(WORKSPACE . $destination, $this->get('validator'), true, 'asc', WORKSPACE);

			if (is_null($states['filelist']) || empty($states['filelist'])) $states['filelist'] = array();

			$directoryOptions = array();
			foreach($states['filelist'] as $handle => $v){
				$directoryOptions[] = array($destination . '/' . General::sanitize($v), in_array($destination . '/' . General::sanitize($v), $data['file']), $v);
			}
			$options[] = array('label' => $destination . '/', 'options' => $directoryOptions);

			// TODO recursive method
			if ($this->get('allow_subdirectories') == 'yes' && is_array($states['dirlist']) && !empty($states['dirlist'])) {
				foreach($states['dirlist'] as $directory){
					$directoryOptions = array();

					if (is_array($states[$destination . '/' . $directory . '/']['filelist']) && !empty($states[$destination . '/' . $directory . '/']['filelist'])) {
						foreach($states[$destination . '/' . $directory . '/']['filelist'] as $handle => $v){
							$directoryOptions[] = array($destination . '/' . $directory . '/' . General::sanitize($v), in_array($destination . '/' . $directory . '/' . General::sanitize($v), $data['file']), $v);
						}
						$options[] = array('label' => $destination . '/' . $directory . '/', 'options' => $directoryOptions);
					}
				}
			}

			// Get stage settings
			$settings = implode(' ', Stage::getComponents($this->get('id'))) . ($this->get('show_preview') == 1 ? ' preview' : '') . ($this->get('allow_multiple_selection') == 'yes' ? ' multiple' : ' single') . ($this->get('allow_subdirectories') == 'yes' ? ' subdirectories' : '');

			$label = Widget::Label($this->get('label'));

			$label->appendChild(Widget::Select($fieldname, $options, ($this->get('allow_multiple_selection') == 'yes' ? array('multiple' => 'multiple') : NULL)));

			$wrapper->setAttribute('data-fieldname', 'fields['.$this->get('sortorder').'][destination]');
			$wrapper->setAttribute('data-stage-settings', $settings);
			$wrapper->appendChild($label);

			// Create stage
			$stage = new XMLElement('div', NULL, array('class' => 'stage ' . $settings));
			$empty = new XMLElement('li', NULL, array('class' => 'empty message'));
			$empty->appendChild(new XMLElement('span', __('There are no selected items')));
			$selected = new XMLElement('ul', NULL, array('class' => 'selection'));
			$selected->appendChild($empty);
			
			foreach ($data['file'] as $file) {
				$listItem = new XMLElement('li', NULL, array('data-value' => $file));
				$inner = new XMLElement('span');
				if (General::validateString($file, '/\.(?:bmp|gif|jpe?g|png)$/i')) {
					$image = new XMLElement('img');
					$image->setAttribute('src', URL . '/image/2/40/40/5' . $file);
					$listItem->appendChild($image);
					$listItem->setAttribute('class', 'preview');
					$inner->setAttribute('class', 'image file');
				}
				$inner->appendChild(new XMLElement('em', dirname($file) . '/'));
				$inner->appendChild(new XMLElement('br'));
				$inner->setValue(basename($file), false);
				$listItem->appendChild($inner);
				$listItem->appendChild(Widget::Input($fieldname, $file, 'hidden'));
				$selected->appendChild($listItem);
			}
			
			// item template
			// $thumb = '<img src="' . URL . '/extensions/uploadselectboxfield/assets/images/new.gif" width="40" height="40" class="thumb" />';
			// $item = new XMLElement('li', $thumb . '<span class="image file">' . __('New item') . '<br /><em>' . __('Please fill out the form below.') . '</em></span><a class="destructor">&#215;</a>', array('class' => 'template create preview'));
			// $selected->appendChild($item);
			
			// drawer template
			// $drawer = new XMLElement('li', NULL, array('class' => 'template drawer'));
			// $drawer->appendChild(new XMLElement('div', NULL, array('class' => 'uploader')));
			// $selected->appendChild($drawer);

			$stage->appendChild($selected);

			if($flagWithError != NULL) {
				$wrapper->appendChild(Widget::wrapFormElementWithError($stage, $flagWithError));
			}
			else {
				$wrapper->appendChild($stage);
			}
		}

		function prepareTableValue($data, XMLElement $link=NULL){
			$value = $data['file'];

			if(!is_array($value)) $value = array($value);

			$custom_link = "";

			foreach($value as $file) {
				if($link){
					$link->setValue(basename($file));
					$custom_link[] = $link->generate();
				}
				else{
					$link = Widget::Anchor(basename($file), URL . $this->get('destination') . '/'. $file);
					$custom_link[] = $link->generate();
				}
			}

			return implode(", ", $custom_link);
		}

		public function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');

			if (preg_match('/^mimetype:/', $data[0])) {
				$data[0] = str_replace('mimetype:', '', $data[0]);
				$column = 'mimetype';

			} else if (preg_match('/^size:/', $data[0])) {
				$data[0] = str_replace('size:', '', $data[0]);
				$column = 'size';

			} else {
				$column = 'file';
			}

			if (self::isFilterRegex($data[0])) {
				$this->_key++;
				$pattern = str_replace('regexp:', '', $this->cleanValue($data[0]));
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND t{$field_id}_{$this->_key}.{$column} REGEXP '{$pattern}'
				";

			} elseif ($andOperation) {
				foreach ($data as $value) {
					$this->_key++;
					$value = $this->cleanValue($value);
					$joins .= "
						LEFT JOIN
							`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
							ON (e.id = t{$field_id}_{$this->_key}.entry_id)
					";
					$where .= "
						AND t{$field_id}_{$this->_key}.{$column} = '{$value}'
					";
				}

			} else {
				if (!is_array($data)) $data = array($data);

				foreach ($data as &$value) {
					$value = $this->cleanValue($value);
				}

				$this->_key++;
				$data = implode("', '", $data);
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND t{$field_id}_{$this->_key}.{$column} IN ('{$data}')
				";
			}

			return true;
		}

		function commit(){

			if(!parent::commit()) return false;

			$id = $this->get('id');

			if($id === false) return false;

			$fields = array();

			$fields['field_id'] = $id;
			$fields['destination'] = $this->get('destination');
			$fields['allow_multiple_selection'] = ($this->get('allow_multiple_selection') ? $this->get('allow_multiple_selection') : 'no');
			$fields['allow_subdirectories'] = ($this->get('allow_subdirectories') ? $this->get('allow_subdirectories') : 'no');
			$fields['validator'] = ($fields['validator'] == 'custom' ? NULL : $this->get('validator'));

			// Save new stage settings for this field
			Stage::saveSettings($this->get('id'), $this->get('stage'), 'subsectionmanager');

			$this->_engine->Database->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
			return $this->_engine->Database->insert($fields, 'tbl_fields_' . $this->handle());

		}

		public function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){

			$status = self::__OK__;

			if(!is_array($data)) {
				$mimetype = $this->getMimetype(WORKSPACE . '/' . $data);
				return array(
					'file' => General::sanitize($data),
					'meta' => serialize($this->getMetaInfo(WORKSPACE . '/' . $data, $mimetype)),
					'mimetype' => $mimetype,
					'size' => (file_exists(WORKSPACE . '/' . $data) && is_readable(WORKSPACE . '/' . $data) ? filesize(WORKSPACE . '/' . $data) : 'unknown')
				);
			}

			if(empty($data)) return NULL;

			$result = array(
				'file' => array(),
				'meta' => array(),
				'mimetype' => array(),
				'size' => array()
			);

			foreach($data as $file) {
				$mimetype = $this->getMimetype(WORKSPACE . '/' . $file);
				$result['file'][] = General::sanitize($file);
				$result['meta'][] = serialize($this->getMetaInfo(WORKSPACE . '/' . $file, $mimetype));
				$result['mimetype'][] = $mimetype;
				$result['size'][] = (file_exists(WORKSPACE . '/' . $file) && is_readable(WORKSPACE . '/' . $file) ? filesize(WORKSPACE . '/' . $file) : 'unknown');
			}

			return $result;
		}

		function createTable(){

			return Symphony::Database()->query(

				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `file` varchar(255) default NULL,
				  `size` int(11) unsigned NULL,
				  `mimetype` varchar(50) default NULL,
				  `meta` varchar(255) default NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`),
				  KEY `file` (`file`),
				  KEY `mimetype` (`mimetype`)
				) ENGINE=MyISAM;"

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

				$extension = $this->getFileExtension($file);
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

