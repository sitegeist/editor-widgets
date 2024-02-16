CREATE TABLE sys_file_storage
(
	tx_editor_widgets_max_size VARCHAR(255) NOT NULL DEFAULT '1GB'
);

CREATE TABLE tx_editor_widgets_broken_link
(
	linkvalidator_link VARCHAR(255) DEFAULT '' NOT NULL UNIQUE,
	suppressed SMALLINT UNSIGNED DEFAULT '0' NOT NULL,
);
