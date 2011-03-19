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
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/uploadselectboxfield/lib/stage/stage.publish.js', 101, false);
			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/uploadselectboxfield/lib/stage/stage.publish.css', 'screen', 103, false);
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/uploadselectboxfield/assets/uploadselectboxfield.publish.js', 104, false);

			if(!is_array($data['file'])) $data['file'] = array($data['file']);

			$options = array();

			if ($this->get('required') != 'yes') $options[] = array(NULL, false, NULL);

			$states = General::listStructure(DOCROOT . $this->get('destination'), $this->get('validator'), true, 'asc', DOCROOT);

			if (is_null($states['filelist']) || empty($states['filelist'])) $states['filelist'] = array();

			foreach($states['filelist'] as $handle => $v){
				$options[] = array(General::sanitize($v), in_array($v, $data['file']), $v);
			}

			if (is_array($states['dirlist']) && !empty($states['dirlist'])) {
				foreach($states['dirlist'] as $directory){
					$directoryOptions = array();

					if (is_array($states[$this->get('destination') . '/' . $directory . '/']['filelist']) && !empty($states[$this->get('destination') . '/' . $directory . '/']['filelist'])) {
						foreach($states[$this->get('destination') . '/' . $directory . '/']['filelist'] as $handle => $v){
							$directoryOptions[] = array(General::sanitize($v), in_array($v, $data['file']), $v);
						}
						$options[] = array('label' => $directory, 'options' => $directoryOptions);
					}
				}
			}

			$fieldname = 'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix;
			if($this->get('allow_multiple_selection') == 'yes') $fieldname .= '[]';

			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Select($fieldname, $options, ($this->get('allow_multiple_selection') == 'yes' ? array('multiple' => 'multiple') : NULL)));

			// if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			// else $wrapper->appendChild($label);
			$wrapper->appendChild($label);

			// Get stage settings
			$settings = ' ' . implode(' ', Stage::getComponents($this->get('id')));

			// Create stage
			$stage = new XMLElement('div', NULL, array('class' => 'stage' . $settings . ($this->get('show_preview') == 1 ? ' preview' : '') . ($this->get('allow_multiple_selection') == 'yes' ? ' multiple' : ' single')));
			$content['empty'] = '<li class="empty message"><span>' . __('There are no selected items') . '</span></li>';
			$selected = new XMLElement('ul', $content['empty'] . $content['html'], array('class' => 'selection'));
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
			$fields['validator'] = ($fields['validator'] == 'custom' ? NULL : $this->get('validator'));

			// Save new stage settings for this field
			Stage::saveSettings($this->get('id'), $this->get('stage'), 'subsectionmanager');

			$this->_engine->Database->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
			return $this->_engine->Database->insert($fields, 'tbl_fields_' . $this->handle());

		}

		public function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){

			$status = self::__OK__;

			if(!is_array($data)) return array('file' => General::sanitize($data));

			if(empty($data)) return NULL;

			$result = array('file' => array());

			foreach($data as $file) {
				$result['file'][] = $file;
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
				  UNIQUE KEY `entry_id` (`entry_id`),
				  KEY `file` (`file`),
				  KEY `mimetype` (`mimetype`)
				) ENGINE=MyISAM;"

			);
		}

	}

