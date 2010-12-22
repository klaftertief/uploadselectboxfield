<?php

	require_once(TOOLKIT . '/fields/field.select.php');

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	require_once(EXTENSIONS . '/uploadselectboxfield/lib/stage/class.stage.php');

	Class FieldUploadselectbox extends Field {
		protected $_driver = null;

		function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = 'Upload Select Box';
			$this->_required = true;
			$this->_driver = $this->_engine->ExtensionManager->create('uploadselectboxfield');

			$this->set('show_column', 'no');
			$this->set('required', 'yes');
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

		public function appendFormattedElement(&$wrapper, $data) {

			// It is possible an array of NULL data will be passed in. Check for this.
			if(!is_array($data) || !isset($data['file']) || is_null($data['file'])){
				return;
			}
			
			$element = new XMLElement($this->get('element_name'));
			
			// single selection mode
			if ($this->get('allow_multiple_selection') == 'no') {
				$file = WORKSPACE . $data['file'];
				$element->setAttributeArray(array(
					'size' => (file_exists($file) && is_readable($file) ? General::formatFilesize(filesize($file)) : 'unknown'),
				 	'path' => str_replace(WORKSPACE, NULL, dirname(WORKSPACE . $data['file'])),
					'type' => $data['mimetype'],
				));

				$element->appendChild(new XMLElement('filename', General::sanitize(basename($data['file']))));

				$m = unserialize($data['meta']);

				if(is_array($m) && !empty($m)){
					$element->appendChild(new XMLElement('meta', NULL, $m));
				}
			}
			// multiple selection mode
			else {
				// only one file
				if (!is_array($data['file'])) {
					$item = new XMLElement('item');
					
					$file = WORKSPACE . $data['file'];
					$item->setAttributeArray(array(
						'size' => (file_exists($file) && is_readable($file) ? General::formatFilesize(filesize($file)) : 'unknown'),
					 	'path' => str_replace(WORKSPACE, NULL, dirname(WORKSPACE . $data['file'])),
						'type' => $data['mimetype'],
					));

					$item->appendChild(new XMLElement('filename', General::sanitize(basename($data['file']))));

					$m = unserialize($data['meta']);

					if(is_array($m) && !empty($m)){
						$item->appendChild(new XMLElement('meta', NULL, $m));
					}
					$element->appendChild($item);
				}
				// multiple files
				else {
					foreach ($data['file'] as $index => $value) {
						$file = WORKSPACE . $data['file'][$index];
						$item = new XMLElement('item');
						$item->setAttributeArray(array(
							'size' => (file_exists($file) && is_readable($file) ? General::formatFilesize(filesize($file)) : 'unknown'),
						 	'path' => str_replace(WORKSPACE, NULL, dirname(WORKSPACE . $data['file'][$index])),
							'type' => $data['mimetype'][$index],
						));
						$item->appendChild(new XMLElement('filename', General::sanitize(basename($data['file'][$index]))));

						$m = unserialize($data['meta'][$index]);

						if(is_array($m) && !empty($m)){
							$item->appendChild(new XMLElement('meta', NULL, $m));
						}

						$element->appendChild($item);
					}
				}
			}
			
			$wrapper->appendChild($element);
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
			$this->appendShowColumnCheckbox($wrapper);

			// Behaviour
			$fieldset = Stage::displaySettings(
				$this->get('id'), 
				$this->get('sortorder'), 
				__('Behaviour')
			);

			// Handle missing settings
			if(!$this->get('id') && $errors == NULL) {
				$this->set('allow_multiple_selection', 1);
			}
			
			// Setting: allow multiple
			$setting = new XMLElement('label', '<input name="fields[' . $this->get('sortorder') . '][allow_multiple_selection]" value="1" type="checkbox"' . ($this->get('allow_multiple_selection') == 'no' ? '' : ' checked="checked"') . '/> ' . __('Allow selection of multiple items') . ' <i>' . __('This will switch between single and multiple item lists') . '</i>');
			$fieldset->appendChild($setting);
			
			// Append behaviour settings
			$wrapper->appendChild($fieldset);

		}

		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			
			$this->_engine->Page->addScriptToHead(URL . '/extensions/uploadselectboxfield/lib/stage/stage.publish.js', 101, false);
			$this->_engine->Page->addStylesheetToHead(URL . '/extensions/uploadselectboxfield/lib/stage/stage.publish.css', 'screen', 103, false);
			$this->_engine->Page->addScriptToHead(URL . '/extensions/uploadselectboxfield/assets/symphony.uploadselectboxfield.js', 102, false);
			$this->_engine->Page->addStylesheetToHead(URL . '/extensions/uploadselectboxfield/assets/symphony.uploadselectboxfield.css', 'screen', 104, false);
			
			if(!is_array($data['file'])) $data['file'] = array($data['file']);
			
			$destination = $this->get('destination');
			$fileList = $this->_driver->createFileList($destination, $data['file']);

			$options = $fileList['options'];

			if ($this->get('required') == 'no') {
				array_unshift($options, array());
			}

			$fieldname = 'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix;
			if($this->get('allow_multiple_selection') == 'yes') $fieldname .= '[]';

			$label = Widget::Label($this->get('label'));
			if($this->get('required') != 'yes') $label->appendChild(new XMLElement('i', __('Optional')));
			$label->appendChild(Widget::Select($fieldname, $options, ($this->get('allow_multiple_selection') == 'yes' ? array('multiple' => 'multiple') : NULL)));

			// Setup destination
			$input = Widget::Input('fields[destination]', $this->get('destination'), 'hidden');
			$label->appendChild($input);
			
			$wrapper->appendChild($label);
			
			// selected items
			$content = array();
			$content['html'] = $fileList['html'];
			
			// Get stage settings
			$settings = ' ' . implode(' ', Stage::getComponents($this->get('id')));
			
			// Create stage
			$stage = new XMLElement('div', NULL, array('class' => 'stage preview' . $settings . ($this->get('allow_multiple_selection') == 'yes' ? ' multiple' : ' single')));
			$content['empty'] = '<li class="empty message"><span>' . __('There are no selected items') . '</span></li>';
			$selected = new XMLElement('ul', $content['empty'] . $content['html'], array('class' => 'selection'));
			$stage->appendChild($selected);
			
			// Append item template
			$thumb = '<img src="' . URL . '/extensions/uploadselectboxfield/assets/images/new.gif" width="40" height="40" class="thumb" />';
			$item = new XMLElement('li', $thumb . '<span>' . __('New item') . '<br /><em>' . __('Please fill out the form below.') . '</em></span><a class="destructor">' . __('Remove Item') . '</a>', array('class' => 'item template preview'));
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
					$link = Widget::Anchor(basename($file), URL . '/workspace'. $file);
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

			// Save new stage settings for this field
			Stage::saveSettings($this->get('id'), $this->get('stage'), 'uploadselectboxfield');

			// Delete old field settings
			Administration::instance()->Database->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
			
			// Save new settings
			return Administration::instance()->Database->insert($fields, 'tbl_fields_' . $this->handle());

		}

		public function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){

			$status = self::__OK__;
			
			if(!is_array($data)) {
				$mimetype = $this->_driver->getMimetype(WORKSPACE . '/' . $data);
				return array(
					'file' => General::sanitize($data),
					'meta' => serialize($this->_driver->getMetaInfo(WORKSPACE . '/' . $data, $mimetype)),
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
				$mimetype = $this->_driver->getMimetype(WORKSPACE . '/' . $file);
				$result['file'][] = General::sanitize($file);
				$result['meta'][] = serialize($this->_driver->getMetaInfo(WORKSPACE . '/' . $file, 'image/jpg'));
				$result['mimetype'][] = $mimetype;
				$result['size'][] = (file_exists(WORKSPACE . '/' . $file) && is_readable(WORKSPACE . '/' . $file) ? filesize(WORKSPACE . '/' . $file) : 'unknown');
			}
			
			return $result;
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

