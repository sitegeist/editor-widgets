CREATE TABLE sys_file_storage
(
	tx_editor_widgets_max_size VARCHAR(255) NOT NULL DEFAULT '1GB'
);

-- pid is necessary for DataHandler
CREATE TABLE tx_linkvalidator_link
(
	tx_editor_widgets_hidden tinyint(1) unsigned DEFAULT '0' NOT NULL,
	pid tinyint(1) unsigned DEFAULT '0' NOT NULL,
);
