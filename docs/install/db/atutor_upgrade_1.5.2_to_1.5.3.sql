###############################################################
# Database upgrade SQL from ATutor 1.5.2 to ATutor 1.5.3
###############################################################

CREATE TABLE `groups_types` (
	`type_id` MEDIUMINT UNSIGNED DEFAULT '0' NOT NULL AUTO_INCREMENT ,
	`course_id` MEDIUMINT UNSIGNED NOT NULL ,
	`title` VARCHAR( 80 ) NOT NULL ,
	PRIMARY KEY ( `type_id` ) ,
	INDEX ( `course_id` )
);

ALTER TABLE `groups` CHANGE `course_id` `type_id` MEDIUMINT( 8 ) UNSIGNED DEFAULT '0' NOT NULL;
ALTER TABLE `groups` ADD `description` TEXT NOT NULL , ADD `modules` VARCHAR(100) NOT NULL;

UPDATE `modules` SET `privilege`=65536 WHERE `dir_name`='_core/groups';
INSERT INTO `modules` VALUES ('_standard/reading_list',  2, 131072,    0);
INSERT INTO `modules` VALUES ('_standard/file_storage',  2, 262144,    0);
INSERT INTO `modules` VALUES ('_standard/assignments',   2, 524288,    0);

# assignments table


# insert assignments into `modules` table


# forum groups table
CREATE TABLE `forums_groups` (
  `forum_id` mediumint( 8 ) unsigned NOT NULL default '0',
  `group_id` mediumint( 8 ) unsigned NOT NULL default '0',
  PRIMARY KEY ( `forum_id` , `group_id` ) ,
  KEY `group_id` ( `group_id` )
) TYPE = MYISAM ;

# release date for courses
ALTER TABLE `courses` ADD `release_date` datetime NOT NULL default '0000-00-00 00:00:00';

# --------------------------------------------------------
# Since 1.5.3
# Table structure for table `reading_list`

CREATE TABLE `reading_list` (
	`reading_id` MEDIUMINT(6) UNSIGNED NOT NULL AUTO_INCREMENT DEFAULT 0,
	`course_id` MEDIUMINT UNSIGNED NOT NULL ,
	`resource_id` MEDIUMINT UNSIGNED NOT NULL,
	`required` enum('required','optional') NOT NULL DEFAULT 'required',
	`date_start` DATE NOT NULL DEFAULT '0000-00-00',
	`date_end` DATE NOT NULL DEFAULT '0000-00-00',
	`comment` text NOT NULL,
	PRIMARY KEY  (`reading_id`),
	INDEX (`course_id`)
) TYPE = MYISAM;

# Since 1.5.3
# Table structure for table `external_resources`

CREATE TABLE `external_resources` (
	`resource_id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT DEFAULT 0,
	`course_id` MEDIUMINT UNSIGNED NOT NULL ,
	`type` TINYINT UNSIGNED NOT NULL DEFAULT 0,
	`title` varchar(255) NOT NULL DEFAULT '',
	`author` varchar(150) NOT NULL DEFAULT '',
	`publisher` varchar(150) NOT NULL DEFAULT '',
	`date` varchar(20) NOT NULL DEFAULT '',
	`comments` varchar(255) NOT NULL DEFAULT '',
	`id` varchar(50) NOT NULL DEFAULT '',
	`url` varchar(255) NOT NULL DEFAULT '',
	PRIMARY KEY (`resource_id`),
	INDEX (`course_id`)
) TYPE = MYISAM;

# for the file storage
# --------------------------------------------------------

CREATE TABLE `file_storage_groups` (
  `group_id` MEDIUMINT UNSIGNED NOT NULL ,
  PRIMARY KEY ( `group_id` )
);


CREATE TABLE `files` (
  `file_id` mediumint(8) unsigned NOT NULL auto_increment,
  `owner_type` tinyint(3) unsigned NOT NULL default '0',
  `owner_id` mediumint(8) unsigned NOT NULL default '0',
  `member_id` mediumint(8) unsigned NOT NULL default '0',
  `folder_id` mediumint(8) unsigned NOT NULL default '0',
  `parent_file_id` mediumint(8) unsigned NOT NULL default '0',
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `num_comments` tinyint(3) unsigned NOT NULL default '0',
  `num_revisions` tinyint(3) unsigned NOT NULL default '0',
  `file_name` varchar(80) NOT NULL default '',
  `file_size` int(11) NOT NULL default '0',
  `comments` text NOT NULL,
  PRIMARY KEY  (`file_id`)
) TYPE=MyISAM;

CREATE TABLE `files_comments` (
  `comment_id` mediumint(8) unsigned NOT NULL auto_increment,
  `file_id` mediumint(8) unsigned NOT NULL default '0',
  `member_id` mediumint(8) unsigned NOT NULL default '0',
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `comment` text NOT NULL,
  PRIMARY KEY  (`comment_id`)
) TYPE=MyISAM;

CREATE TABLE `folders` (
  `folder_id` mediumint(8) unsigned NOT NULL auto_increment,
  `parent_folder_id` mediumint(8) unsigned NOT NULL default '0',
  `owner_type` tinyint(3) unsigned NOT NULL default '0',
  `owner_id` mediumint(8) unsigned NOT NULL default '0',
  `title` varchar(30) NOT NULL default '',
  PRIMARY KEY  (`folder_id`)
) TYPE=MyISAM;