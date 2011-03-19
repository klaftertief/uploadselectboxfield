<?php

	Class extension_Uploadselectboxfield extends Extension{

		public function __construct(Array $args){
			parent::__construct($args);

			// Include Stage
			if(!class_exists('Stage')) {
				try {
					if((include_once(EXTENSIONS . '/uploadselectboxfield/lib/stage/class.stage.php')) === FALSE) {
						throw new Exception();
					}
				}
				catch(Exception $e) {
				    throw new SymphonyErrorPage(__('Please make sure that the Stage submodule is initialised and available at %s.', array('<code>' . EXTENSIONS . '/uploadselectboxfield/lib/stage/</code>')) . '<br/><br/>' . __('It\'s available at %s.', array('<a href="https://github.com/nilshoerrmann/stage">github.com/nilshoerrmann/stage</a>')), __('Stage not found'));
				}
			}
		}

		public function about(){
			return array('name' => 'Field: Uploaded File Select Box',
						 'version' => '1.2',
						 'release-date' => '2011-03-18',
						 'author' => array('name' => 'Nick Dunn, Brendan Abbott',
										   'website' => 'http://nick-dunn.co.uk')
				 		);
		}

		public function uninstall(){
			Symphony::Database()->query("DROP TABLE IF NOT EXISTS `tbl_fields_uploadselectbox`");

			// Drop related entries from stage tables
			Symphony::Database()->query("DELETE FROM `tbl_fields_stage` WHERE `context` = 'uploadselectbox'");
			Symphony::Database()->query("DELETE FROM `tbl_fields_stage_sorting` WHERE `context` = 'uploadselectbox'");
		}

		public function install(){
			$status = array();

			// Create database field table
			$status[] = Symphony::Database()->query("CREATE TABLE IF NOT EXISTS `tbl_fields_uploadselectbox` (
				`id` int(11) unsigned NOT NULL auto_increment,
				`field_id` int(11) unsigned NOT NULL,
				`allow_multiple_selection` enum('yes','no') NOT NULL default 'no',
				`destination` varchar(255) NOT NULL,
				`validator` varchar(255) default NULL,
				`allow_subfolders` enum('yes','no') NOT NULL default 'no',
				PRIMARY KEY  (`id`),
				UNIQUE KEY `field_id` (`field_id`)
			) TYPE=MyISAM");

			// Create stage
			$status[] = Stage::install();

			// Report status
			if(in_array(false, $status, true)) {
				return false;
			} else {
				return true;
			}
		}

		public function update($previousVersion){
			if (version_compare($previousVersion, '1.2', '<')) {

				// Install missing tables
				$this->install();

				// Update existind field settings table
				Symphony::Database()->query(
					"ALTER TABLE `tbl_fields_uploadselectbox`
					ADD COLUMN `validator` varchar(255) default NULL,
					ADD COLUMN `allow_subfolders` enum('yes','no') NOT NULL default 'no'"
				);

				// Update existing field data tables
				$fields = array();
				// Find fields (field_id and destination)
				$fields = Symphony::Database()->fetch(
					"SELECT field_id, destination
					FROM tbl_fields_uploadselectbox"
				);

				if (!empty($fields)) {
					foreach ($fields as $field) {
						$destination = str_replace('/workspace', '', $field['destination']) . '/';

						// Add size, mimetype and meta columns
						Symphony::Database()->query(
							"ALTER TABLE `tbl_entries_data_{$field['field_id']}`
							ADD COLUMN `size` int(11) unsigned NULL,
							ADD COLUMN `mimetype` varchar(50) default NULL,
							ADD COLUMN `meta` varchar(255) default NULL,
							ADD KEY `file` (`file`),
							ADD KEY `mimetype` (`mimetype`)"
						);

						// prepend content of file column with field destination
						Symphony::Database()->query(
							"UPDATE `tbl_entries_data_{$field['field_id']}`
							SET `file` = CONCAT('{$destination}', `file`)"
						);
					}
				}

			}
		}

	}