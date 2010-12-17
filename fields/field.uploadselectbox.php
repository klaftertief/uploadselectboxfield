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

			$options = array();
			$states = General::listStructure(DOCROOT . $this->get('destination'), null, false, 'asc', DOCROOT);
			
			if (is_null($states['filelist']) || empty($states['filelist'])) $states['filelist'] = array();
			
			foreach($states['filelist'] as $handle => $v){
				$options[] = array(General::sanitize($v), in_array($v, $data['file']), $v);
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
			$path = str_replace('/workspace', '', $this->get('destination'));
			// TODO: creat abstracted method
			foreach($states['filelist'] as $handle => $v){
				if (in_array($v, $data['file'])) {
					$items[] = '<li class="preview" data-value="' . $v . '"><img src="' . URL . '/image/2/40/40/5' . $path . '/' . $v . '" width="40" height="40" /><a href="' . URL . $this->get('destination') . '/' . $v . '" class="image file">' . $v . '</a></li>';
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
				return array(
					'file' => General::sanitize($data),
					'meta' => serialize(self::getMetaInfo(WORKSPACE . str_replace('/workspace', '', $this->get('destination')) . '/' . $data, 'image/jpg'))
				);
			}

			if(empty($data)) return NULL;

			$result = array('file' => array());

			foreach($data as $file) {
				$result['file'][] = $file;
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

