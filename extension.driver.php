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

	}