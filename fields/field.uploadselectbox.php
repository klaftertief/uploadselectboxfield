<?php

	require_once(TOOLKIT . '/fields/field.select.php');

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	Class FieldUploadselectbox extends Field {

		function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = 'Upload Select Box';
			$this->set('show_column', 'no');
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

			if (!is_array($data['file']) && !is_null($data['file'])) {
				$data = array(
					'file' => array($data['file'])
				);
			}
			else {
				return;
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
			$directories = General::listDirStructure(WORKSPACE, true, 'asc', DOCROOT, $ignore);

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

			$this->appendRequiredCheckbox($wrapper);

			## Allow selection of multiple items
			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][allow_multiple_selection]', 'yes', 'checkbox');
			if($this->get('allow_multiple_selection') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue(__('%s Allow selection of multiple options', array($input->generate())));
			$wrapper->appendChild($label);

			$this->appendShowColumnCheckbox($wrapper);

		}

		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			
			$this->_engine->Page->addScriptToHead(URL . '/extensions/uploadselectboxfield/lib/draggable/symphony.draggable.js', 101, false);
			$this->_engine->Page->addScriptToHead(URL . '/extensions/uploadselectboxfield/lib/stage/symphony.stage.js', 101, false);
			$this->_engine->Page->addStylesheetToHead(URL . '/extensions/uploadselectboxfield/lib/stage/symphony.stage.css', 'screen', 103, false);
			$this->_engine->Page->addScriptToHead(URL . '/extensions/uploadselectboxfield/assets/symphony.uploadselectboxfield.js', 102, false);
			$this->_engine->Page->addStylesheetToHead(URL . '/extensions/uploadselectboxfield/assets/symphony.uploadselectboxfield.css', 'screen', 104, false);
			
			if(!is_array($data['file'])) $data['file'] = array($data['file']);
			
			$destination = $this->get('destination');
			$abs_path = DOCROOT . '/' . trim($destination, '/');
			$rel_path = str_replace('/workspace', '', $destination);
			$options = array();
			
			$states = General::listStructure(DOCROOT . $destination, null, false, 'asc', DOCROOT);
			
			if (is_null($states['filelist']) || empty($states['filelist'])) $states['filelist'] = array();
			
			foreach($states['filelist'] as $handle => $v){
				$options[] = array($rel_path . '/' . General::sanitize($v), in_array($rel_path . '/' . $v, $data['file']), $v);
			}

			$fieldname = 'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix;
			if($this->get('allow_multiple_selection') == 'yes') $fieldname .= '[]';

			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Select($fieldname, $options, ($this->get('allow_multiple_selection') == 'yes' ? array('multiple' => 'multiple') : NULL)));

			// Setup destination
			$input = Widget::Input('fields[destination]', $this->get('destination'), 'hidden');
			$label->appendChild($input);
			
			$wrapper->appendChild($label);
			
			// selected items
			$content = array();
			$items = array();
			// TODO: create abstracted method
			foreach($states['filelist'] as $handle => $v){
				if (in_array($rel_path . '/' . $v, $data['file'])) {
					$items[] = '<li class="preview" data-value="' . $v . '"><img src="' . URL . '/image/2/40/40/5' . $rel_path . '/' . $v . '" width="40" height="40" /><a href="' . URL . $destination . '/' . $v . '" class="image file">' . $v . '</a></li>';
				}
			}
			$content['html'] = implode('', $items);
			
			// Create stage
			$stage = new XMLElement('div', NULL, array('class' => 'stage preview searchable constructable destructable'));
			$content['empty'] = '<li class="empty message"><span>' . __('There are no selected items') . '</span></li>';
			$selected = new XMLElement('ul', $content['empty'] . $content['html'], array('class' => 'selection'));
			$stage->appendChild($selected);
			
			// Append item template
			$thumb = '<img src="' . URL . '/extensions/uploadselectboxfield/assets/images/new.gif" width="40" height="40" class="thumb" />';
			$item = new XMLElement('li', $thumb . '<span>' . __('New item') . '<br /><em>' . __('Please fill out the form below.') . '</em></span><a class="destructor">' . __('Remove Item') . '</a>', array('class' => 'item template preview'));
			$selected->appendChild($item);
			
			// Append drawer template
			$subsection_handle = Administration::instance()->Database->fetchVar('handle', 0,
				"SELECT `handle`
				FROM `tbl_sections`
				WHERE `id` = '" . $this->get('subsection_id') . "'
				LIMIT 1"
			);
			$create_new = URL . '/symphony/publish/' . $subsection_handle;
			$item = new XMLElement('li', '<iframe name="subsection-' . $this->get('element_name') . '" src="about:blank" target="' . $create_new . '"  frameborder="0"></iframe>', array('class' => 'drawer template'));
			$selected->appendChild($item);

			// Error handling
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

			$this->_engine->Database->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
			return $this->_engine->Database->insert($fields, 'tbl_fields_' . $this->handle());

		}

		public function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){

			$status = self::__OK__;
			
			if(!is_array($data)) {
				$mimetype = $this->getMimetype(WORKSPACE . '/' . $data);
				return array(
					'file' => General::sanitize($data),
					'meta' => serialize(self::getMetaInfo(WORKSPACE . '/' . $data, $mimetype)),
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
				$result['meta'][] = serialize(self::getMetaInfo(WORKSPACE . '/' . $file, 'image/jpg'));
				$result['mimetype'][] = $mimetype;
				$result['size'][] = (file_exists(WORKSPACE . '/' . $file) && is_readable(WORKSPACE . '/' . $file) ? filesize(WORKSPACE . '/' . $file) : 'unknown');
			}
			
			return $result;
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

		function getMimetype($file) {
			
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

		function createTable(){

			return $this->_engine->Database->query(

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
				) TYPE=MyISAM ;	"

			);
		}

	}

