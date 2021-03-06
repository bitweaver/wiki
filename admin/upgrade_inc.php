<?php

global $gBitSystem, $gUpgradeFrom, $gUpgradeTo;

$upgrades = array(

'TIKIWIKI19' => array (
	'TIKIWIKI18' => array (
/* Sliced and diced TW 1.9 upgrade scripts that did actual schema alterations

ALTER TABLE `tiki_pages`  modify  `wiki_cache` INT( 10  ) default null;
ALTER TABLE `tiki_pages` ADD `lang` VARCHAR( 16 ) AFTER `page_size` ;
ALTER TABLE `tiki_pages` ADD `lockedby` VARCHAR(200) default NULL;
ALTER TABLE  `tiki_pages` DROP column `lock`;
ALTER TABLE tiki_history ADD version_minor int(8) NOT NULL default 0 AFTER version;
ALTER TABLE `tiki_pages` ADD `is_html` TINYINT(1) default 0;
ALTER TABLE `tiki_pages` ADD created int(14);

*/
	)
),

'BONNIE' => array(
	'BWR1' => array(

// STEP 0
array( 'QUERY' =>
	array( 'MYSQL' => array(
	"ALTER TABLE `".BIT_DB_PREFIX."tiki_history` DROP PRIMARY KEY",
	"ALTER TABLE `".BIT_DB_PREFIX."tiki_links` DROP PRIMARY KEY",
// jht 2005-06-19_00:05:40 adding the following two indexes significantly speeds up large TikiWiki 1.8 upgrades
	"ALTER TABLE ".BIT_DB_PREFIX."tiki_pages ADD INDEX version (version)",
	"ALTER TABLE ".BIT_DB_PREFIX."tiki_history ADD INDEX version (version)",

	)),
),

// STEP 1
array( 'DATADICT' => array(
array( 'RENAMECOLUMN' => array(
	'tiki_pages' => array( '`pageRank`' => '`page_rank` N(4,3)' ),
	'tiki_received_pages' => array( '`receivedPageId`' => '`received_page_id` I4 AUTO',
//									'`pageName`' => '`page_name`' ,
									'`receivedFromSite`' => '`received_from_site` C(200)' ,
									'`receivedFromUser`' => '`received_from_user` C(200)' ,
									'`receivedDate`' => '`received_date` I8' ,
									),
	'tiki_actionlog' => array( '`lastModif`' => '`last_modified` I8' ),
	'tiki_history' => array( '`lastModif`' => '`last_modified` I8'	),
	'tiki_copyrights' => array( '`copyrightId`' => '`copyright_id` I4 AUTO' ),
	'tiki_extwiki' => array( '`extwikiId`' => '`extwiki_id` I4 AUTO' ),
	'tiki_tags' => array(	'`tagName`' => '`tag_name` C(80)',
							'`pageName`' => '`page_name` C(160)',
							'`lastModif`' => '`last_modified` I8' ),
)),
array( 'ALTER' => array(
	'tiki_pages' => array(
		'content_id' => array( '`content_id`', 'I4' ), // , 'NOTNULL' ),
	),
	'tiki_copyrights' => array(
		'user_id' => array( '`user_id`', 'I4' ), // , 'NOTNULL' ),
		'page_id' => array( '`page_id`', 'I4' ), // , 'NOTNULL' ),
	),
	'tiki_page_footnotes' => array(
		'user_id' => array( '`user_id`', 'I4' ), // , 'NOTNULL' ),
		'page_id' => array( '`page_id`', 'I4' ), // , 'NOTNULL' ),
	),
	'tiki_actionlog' => array(
		'user_id' => array( '`user_id`', 'I4' ), // , 'NOTNULL' ),
		'page_id' => array( '`page_id`', 'I4' ), // , 'NOTNULL' ),
	),
	'tiki_history' => array(
		'page_id' => array( '`page_id`', 'I4' ), // , 'NOTNULL' ),
		'user_id' => array( '`user_id`', 'I4' ), // , 'NOTNULL' ),
		'format_guid' => array( 'format_guid', 'VARCHAR(16)' ), // , 'NOTNULL' ),
	),
	'tiki_links' => array(
		'from_content_id' => array( '`from_content_id`', 'I4' ), // , 'NOTNULL' ),
		'to_content_id' => array( '`to_content_id`', 'I4' ), // , 'NOTNULL' ),
	),
)),
)),

// STEP 3
array( 'PHP' => '
	global $gBitSystem;
	require_once( WIKI_PKG_CLASS_PATH.'BitPage.php' );
	$max = $gBitSystem->mDb->getOne( "SELECT MAX(`page_id`) FROM `'.BIT_DB_PREFIX.'tiki_pages`" );
	$gBitSystem->mDb->CreateSequence( "tiki_pages_page_id_seq", $max + 1 );
	$query = "SELECT uu.`user_id`, uu2.`user_id` AS modifier_user_id, tp.`lastModif` AS created, tp.`lastModif` AS `last_modified`, tp.`data`, tp.`pageName` AS `title`, tp.`ip`, tp.`hits`
			  FROM `'.BIT_DB_PREFIX.'tiki_pages` tp INNER JOIN `'.BIT_DB_PREFIX.'users_users` uu ON( tp.`creator`=uu.`login` ) INNER JOIN `'.BIT_DB_PREFIX.'users_users` uu2 ON( tp.`user`=uu2.`login` )";
	if( $rs = $gBitSystem->mDb->query( $query ) ) {
		while( !$rs->EOF ) {
			$conId = $gBitSystem->mDb->GenID( "tiki_content_id_seq" );
			$rs->fields["content_id"] = $conId;
			$rs->fields["content_type_guid"] = BITPAGE_CONTENT_TYPE_GUID;
			$rs->fields["format_guid"] = PLUGIN_GUID_TIKIWIKI;
			$gBitSystem->mDb->associateInsert( "tiki_content", $rs->fields );
			$gBitSystem->mDb->query( "UPDATE `'.BIT_DB_PREFIX.'tiki_pages` SET `content_id`=? WHERE `pageName`=?", array( $conId, $rs->fields["title"] ) );
			if( $w_use_dir = $gBitSystem->getConfig("w_use_dir") ) {
				$page = new BitPage( NULL, $conId );
				if( $page->load() && $rs2 = $gBitSystem->mDb->query( "SELECT * FROM `'.BIT_DB_PREFIX.'tiki_wiki_attachments` twa  INNER JOIN `'.BIT_DB_PREFIX.'users_users` uu ON( twa.`user`=uu.`login` ) WHERE twa.`page`=?", array( $rs->fields["title"] ) ) ) {
					while( !$rs2->EOF ) {
						$info = $rs2->fields;
						$storeHash["modifier_user_id"] = $rs->fields["modifier_user_id"];
						$storeHash["upload"]["user_id"] = (!empty($info["user_id"]) ? $info["user_id"] : ROOT_USER_ID);
						$storeHash["upload"]["name"] = $info["filename"];
						$storeHash["upload"]["type"] = $info["filetype"];
						$storeHash["upload"]["size"] = filesize( $w_use_dir.$info["path"] );
						$storeHash["upload"]["tmp_name"] = $w_use_dir.$info["path"];
						if( $page->store( $storeHash ) ) {
							$gBitSystem->mDb->query( "DELETE FROM `'.BIT_DB_PREFIX.'tiki_wiki_attachments` WHERE `page`=?", array( $rs->fields["title"] ) );
						}
						unset( $storeHash );
						$rs2->MoveNext();
					}
				}
				if( !empty( $page->mErrors ) ) {
vd( $page->mErrors );
				}
			}
			$rs->MoveNext();
		}
	}
' ),


// STEP 4
array( 'QUERY' =>
	array( 'SQL92' => array(
//	"UPDATE `".BIT_DB_PREFIX."tiki_pages SET `modifier_user_id`=-1 WHERE `modifier_user_id` IS NULL",
	"UPDATE `".BIT_DB_PREFIX."tiki_history` SET `page_id`= (SELECT `page_id` FROM `".BIT_DB_PREFIX."tiki_pages` tp WHERE tp.`pageName`=`".BIT_DB_PREFIX."tiki_history`.`pageName`)",
	"UPDATE `".BIT_DB_PREFIX."tiki_history` SET `user_id`=(SELECT `user_id` FROM `".BIT_DB_PREFIX."users_users` WHERE `".BIT_DB_PREFIX."users_users`.`login`=`".BIT_DB_PREFIX."tiki_history`.`user`)",
	"UPDATE `".BIT_DB_PREFIX."tiki_history` SET `user_id`=".ROOT_USER_ID." WHERE `user_id` IS NULL",
	"UPDATE `".BIT_DB_PREFIX."tiki_structures` SET `content_id`= (SELECT `content_id` FROM `".BIT_DB_PREFIX."tiki_pages` tp WHERE tp.`page_id`=`".BIT_DB_PREFIX."tiki_structures`.`page_id`)",
	"UPDATE `".BIT_DB_PREFIX."tiki_copyrights` SET `page_id`= (SELECT `page_id` FROM `".BIT_DB_PREFIX."tiki_pages` tp WHERE tp.`pageName`=`".BIT_DB_PREFIX."tiki_copyrights`.`page`)",
	"UPDATE `".BIT_DB_PREFIX."tiki_copyrights` SET `user_id`=(SELECT `user_id` FROM `".BIT_DB_PREFIX."users_users` WHERE `".BIT_DB_PREFIX."users_users`.`login`=`".BIT_DB_PREFIX."tiki_copyrights`.`userName`)",
	"UPDATE `".BIT_DB_PREFIX."tiki_copyrights` SET `user_id`=".ROOT_USER_ID." WHERE `user_id` IS NULL",
	"UPDATE `".BIT_DB_PREFIX."tiki_page_footnotes` SET `page_id`= (SELECT `page_id` FROM `".BIT_DB_PREFIX."tiki_pages` tp WHERE tp.`pageName`=`".BIT_DB_PREFIX."tiki_page_footnotes`.`pageName`)",
	"UPDATE `".BIT_DB_PREFIX."tiki_page_footnotes` SET `user_id`=(SELECT `user_id` FROM `".BIT_DB_PREFIX."users_users` WHERE `".BIT_DB_PREFIX."users_users`.`login`=`".BIT_DB_PREFIX."tiki_page_footnotes`.`user`)",
	"UPDATE `".BIT_DB_PREFIX."tiki_page_footnotes` SET `user_id`=".ROOT_USER_ID." WHERE `user_id` IS NULL",
	"UPDATE `".BIT_DB_PREFIX."tiki_actionlog` SET `page_id`= (SELECT `page_id` FROM `".BIT_DB_PREFIX."tiki_pages` tp WHERE tp.`pageName`=`".BIT_DB_PREFIX."tiki_actionlog`.`pageName`)",
	"UPDATE `".BIT_DB_PREFIX."tiki_actionlog` SET `user_id`=(SELECT `user_id` FROM `".BIT_DB_PREFIX."users_users` WHERE `".BIT_DB_PREFIX."users_users`.`login`=`".BIT_DB_PREFIX."tiki_actionlog`.`user`)",
	"UPDATE `".BIT_DB_PREFIX."tiki_actionlog` SET `user_id`=".ROOT_USER_ID." WHERE `user_id` IS NULL",
	"UPDATE `".BIT_DB_PREFIX."tiki_links` SET `from_content_id`= (SELECT `content_id` FROM `".BIT_DB_PREFIX."tiki_pages` tp WHERE tp.`pageName`=`".BIT_DB_PREFIX."tiki_links`.`fromPage`)",
	"UPDATE `".BIT_DB_PREFIX."tiki_links` SET `to_content_id`= (SELECT `content_id` FROM `".BIT_DB_PREFIX."tiki_pages` tp WHERE tp.`pageName`=`".BIT_DB_PREFIX."tiki_links`.`toPage`)",
	"UPDATE `".BIT_DB_PREFIX."users_permissions` SET perm_name='bit_p_edit_books', perm_desc='Can create and edit books' WHERE perm_name='bit_p_edit_structures'",

	"INSERT INTO `".BIT_DB_PREFIX."users_grouppermissions` (`group_id`, `perm_name`) VALUES (2,'bit_p_edit_books')",

// add in permissions not in TW 1.8 - may get failures on some duplicates
        "INSERT INTO `".BIT_DB_PREFIX."users_permissions` (`perm_name`,`perm_desc`, `level`, `package`) VALUES ('bit_p_userfiles', 'Can upload personal files', 'registered', 'users')",
        "INSERT INTO `".BIT_DB_PREFIX."users_permissions` (`perm_name`,`perm_desc`, `level`, `package`) VALUES ('bit_p_user_group_perms', 'Can assign permissions to personal groups', 'editors', 'users')",
        "INSERT INTO `".BIT_DB_PREFIX."users_permissions` (`perm_name`,`perm_desc`, `level`, `package`) VALUES ('bit_p_user_group_members', 'Can assign users to personal groups', 'registered', 'users')",
        "INSERT INTO `".BIT_DB_PREFIX."users_permissions` (`perm_name`,`perm_desc`, `level`, `package`) VALUES ('bit_p_user_group_subgroups', 'Can include other groups in groups', 'editors', 'users')",
        "INSERT INTO `".BIT_DB_PREFIX."users_permissions` (`perm_name`,`perm_desc`, `level`, `package`) VALUES ('bit_p_create_bookmarks', 'Can create user bookmarksche user bookmarks', 'registered', 'users')",
        "INSERT INTO `".BIT_DB_PREFIX."users_permissions` (`perm_name`,`perm_desc`, `level`, `package`) VALUES ('bit_p_configure_modules', 'Can configure modules', 'registered', 'users')",
        "INSERT INTO `".BIT_DB_PREFIX."users_permissions` (`perm_name`,`perm_desc`, `level`, `package`) VALUES ('bit_p_cache_bookmarks', 'Can cache user bookmarks', 'admin', 'users')",
        "INSERT INTO `".BIT_DB_PREFIX."users_permissions` (`perm_name`,`perm_desc`, `level`, `package`) VALUES ('bit_p_usermenu', 'Can create items in personal menu', 'registered', 'users')",
        "INSERT INTO `".BIT_DB_PREFIX."users_permissions` (`perm_name`,`perm_desc`, `level`, `package`) VALUES ('bit_p_tasks', 'Can use tasks', 'registered', 'users')",
        "INSERT INTO `".BIT_DB_PREFIX."users_permissions` (`perm_name`,`perm_desc`, `level`, `package`) VALUES ('bit_p_assume_users', 'Can assume the identity of other users', 'admin', 'users')",
        "INSERT INTO `".BIT_DB_PREFIX."users_permissions` (`perm_name`,`perm_desc`, `level`, `package`) VALUES ('bit_p_admin_users', 'Can edit the information for other users', 'admin', 'users')",
        "INSERT INTO `".BIT_DB_PREFIX."users_permissions` (`perm_name`,`perm_desc`, `level`, `package`) VALUES ('bit_p_view_tabs_and_tools', 'Can view tab and tool links', 'basic', 'users')",
//users don't have any buttons for page functions without this
	"INSERT INTO `".BIT_DB_PREFIX."users_grouppermissions` (`group_id`, `perm_name`) VALUES (-1,'bit_p_view_tabs_and_tools')",
	"INSERT INTO `".BIT_DB_PREFIX."users_grouppermissions` (`group_id`, `perm_name`) VALUES (1,'bit_p_view_tabs_and_tools')",


	"UPDATE `".BIT_DB_PREFIX."tiki_preferences` SET `name`='feature_wiki_generate_pdf' WHERE name='feature_wiki_pdf'",
	"INSERT INTO `".BIT_DB_PREFIX."tiki_preferences` (`name`, `value`, `package`) VALUES( 'feature_page_title', 'y', 'wiki' )",
	"INSERT INTO `".BIT_DB_PREFIX."tiki_preferences` (`name`, `value`, `package`) VALUES( 'package_wiki', 'y', 'wiki' )",

	// Update versions that are out of whack so tiki_pages.versions>tiki_history.version
	"UPDATE `".BIT_DB_PREFIX."tiki_pages` SET `version`=(SELECT th.`version`+1 FROM `".BIT_DB_PREFIX."tiki_history` th WHERE th.`page_id`=`".BIT_DB_PREFIX."tiki_pages`.`page_id` AND `".BIT_DB_PREFIX."tiki_pages`.`version`=th.`version`) WHERE `page_id` IN (SELECT `page_id` FROM `".BIT_DB_PREFIX."tiki_history` th WHERE th.`version`=`".BIT_DB_PREFIX."tiki_pages`.`version` AND th.`page_id`=`".BIT_DB_PREFIX."tiki_pages`.`page_id`)",

	// should go into users, but has to go here do to wiki needing user changes first
	"UPDATE `".BIT_DB_PREFIX."tiki_content` SET content_type_guid='bituser' WHERE title like 'UserPage%'",
	"UPDATE `".BIT_DB_PREFIX."users_users` SET `content_id`=(SELECT `content_id` FROM `".BIT_DB_PREFIX."tiki_content` WHERE `content_type_guid`='bituser' AND `user_id`=`".BIT_DB_PREFIX."users_users`.`user_id`)",

	// update comments on user pages
	"UPDATE `".BIT_DB_PREFIX."tiki_comments` SET `objectType`='".BITUSER_CONTENT_TYPE_GUID."' WHERE `objectType`='wiki page' AND `object` LIKE 'UserPage%'",
	"UPDATE `".BIT_DB_PREFIX."tiki_comments` SET `parent_id`=(SELECT `content_id` FROM `".BIT_DB_PREFIX."tiki_content` WHERE `content_type_guid`='".BITUSER_CONTENT_TYPE_GUID."' AND `title`=`".BIT_DB_PREFIX."tiki_comments`.`object` ) WHERE `parent_id`=0 AND `objectType`='".BITUSER_CONTENT_TYPE_GUID."'",

	// update comments on wiki pages
	"UPDATE `".BIT_DB_PREFIX."tiki_comments` SET `objectType`='".BITPAGE_CONTENT_TYPE_GUID."' WHERE `objectType`='wiki page'",

	// set parent ID = content ID of parent comment
	// this will only work correctly for TW DB upgrades, and will corrupt the DB if run more then once
	"create temporary table `".BIT_DB_PREFIX."tiki_comments_temp` as (select * from `".BIT_DB_PREFIX."tiki_comments`) ",
	"UPDATE `".BIT_DB_PREFIX."tiki_comments` SET `parent_id`=(SELECT i_tcm.`content_id` FROM `".BIT_DB_PREFIX."tiki_content` as i_tcn, `".BIT_DB_PREFIX."tiki_comments_temp` as i_tcm WHERE  i_tcm.`content_id` = i_tcn.`content_id` and `".BIT_DB_PREFIX."tiki_comments`.`parent_id` = i_tcm.`comment_id` ) where  parent_id != 0 and  `objectType`='".BITPAGE_CONTENT_TYPE_GUID."' ",
	// parent ID = 0 indicates a root comment in TW, but now needs to = content ID of wiki page it is the root comment for
	"UPDATE `".BIT_DB_PREFIX."tiki_comments` SET `parent_id`=(SELECT `content_id` FROM `".BIT_DB_PREFIX."tiki_content` WHERE `content_type_guid`='".BITPAGE_CONTENT_TYPE_GUID."' AND `title`=`".BIT_DB_PREFIX."tiki_comments`.`object` ) WHERE `parent_id`=0 AND `objectType`='".BITPAGE_CONTENT_TYPE_GUID."'",

	"INSERT INTO `".BIT_DB_PREFIX."tiki_preferences` (`name`, `value`, `package`) VALUES( 'feature_wiki_books', 'y', 'wiki' )",
	"INSERT INTO `".BIT_DB_PREFIX."tiki_preferences` (`name`, `value`, `package`) VALUES( 'feature_history', 'y', 'wiki' )",
	"INSERT INTO `".BIT_DB_PREFIX."tiki_preferences` (`name`, `value`, `package`) VALUES( 'feature_listPages', 'y', 'wiki' )",
	"UPDATE `".BIT_DB_PREFIX."tiki_preferences` SET name='allow_html' WHERE name='feature_wiki_allowhtml'",

	"UPDATE `".BIT_DB_PREFIX."tiki_categorized_objects` SET `object_type`='".BITPAGE_CONTENT_TYPE_GUID."', `object_id`=(SELECT tc.`content_id` FROM `".BIT_DB_PREFIX."tiki_content` tc WHERE tc.`title`=`".BIT_DB_PREFIX."tiki_categorized_objects`.`objId` AND `".BIT_DB_PREFIX."tiki_categorized_objects`.`object_type`='wiki page')",


	// update user watches
	"update `".BIT_DB_PREFIX."tiki_user_watches` as `tw` set `object` = (select `tp`.`page_id` from `".BIT_DB_PREFIX."tiki_pages` as `tp`, `tiki_content` as `tc` where `tp`.`content_id` = `tc`.`content_id` and   `tc`.`title` = `tw`.`title` )",


	),
)),

/*
// array( 'sql92' => array(

"ALTER TABLE `".BIT_DB_PREFIX."tiki_wiki_attachments` RENAME COLUMN `attId` TO att_id",
"ALTER TABLE `".BIT_DB_PREFIX."tiki_wiki_attachments ADD user_id INT",
"UPDATE `".BIT_DB_PREFIX."tiki_wiki_attachments SET user_id=(SELECT user_id FROM users_users WHERE `user`=login)",
"UPDATE `".BIT_DB_PREFIX."tiki_wiki_attachments SET user_id=1 WHERE user_id IS NULL",
"ALTER TABLE `".BIT_DB_PREFIX."tiki_wiki_attachments ALTER user_id SET NOT NULL",
"ALTER TABLE `".BIT_DB_PREFIX."tiki_wiki_attachments DROP `user`",
"ALTER TABLE `".BIT_DB_PREFIX."tiki_wiki_attachments ADD page_id INT",
"UPDATE `".BIT_DB_PREFIX."tiki_wiki_attachments SET page_id=(SELECT page_id FROM tiki_pages WHERE `page_name`=`page`)",
"ALTER TABLE `".BIT_DB_PREFIX."tiki_wiki_attachments ALTER page_id SET NOT NULL",
"ALTER TABLE `".BIT_DB_PREFIX."tiki_wiki_attachments DROP `page`",

*/

// STEP 5
array( 'PHP' => '
	global $gBitSystem;
	require_once( LIBERTY_PKG_CLASS_PATH.'LibertyStructure.php' );
	require_once( WIKI_PKG_CLASS_PATH.'BitBook.php' );
	$query = "SELECT `structure_id`, `content_id` FROM `".BIT_DB_PREFIX."tiki_structures` WHERE `parent_id` IS NULL OR `parent_id`=0";
	$roots = $gBitSystem->mDb->getAssoc( $query );
	$s = new LibertyStructure();
	foreach( $roots AS $rootId=>$contentId ) {
		$gBitSystem->mDb->query( "UPDATE `".BIT_DB_PREFIX."tiki_structures` SET `root_structure_id`=? WHERE `structure_id`=?", array( $rootId, $rootId ) );
		$gBitSystem->mDb->query( "UPDATE `".BIT_DB_PREFIX."tiki_content` SET `content_type_guid`=? WHERE `content_id`=?", array( BITBOOK_CONTENT_TYPE_GUID, $contentId ) );
		$toc = $s->buildSubtreeToc( $rootId );
		$s->setTreeRoot( $rootId, $toc );
	}

' ),


// STEP 6
array( 'DATADICT' => array(
	array( 'DROPCOLUMN' => array(
		'tiki_pages' => array( '`lastModif`', '`data`', '`pageName`', '`ip`', '`hits`', '`user`', '`creator`' ),
		'tiki_copyrights' => array( '`userName`', '`page`' ),
		'tiki_page_footnotes' => array( '`user`', '`pageName`' ),
		'tiki_actionlog' => array( '`user`', '`pageName`' ),
		'tiki_history' => array( '`user`', '`pageName`' ),
		'tiki_links' => array( '`fromPage`', '`toPage`' ),
		'tiki_structures' => array( '`page_id`' ),
	)),
)),

// STEP 7
array( 'DATADICT' => array(
array( 'CREATEINDEX' => array(
		'tiki_actlog_page_idx' => array( 'tiki_actionlog', '`page_id`', array() ),
		'tiki_copyrights_page_idx' => array( 'tiki_copyrights', '`page_id`', array() ),
		'tiki_copyrights_user_idx' => array( 'tiki_copyrights', '`user_id`', array() ),
		'tiki_copyrights_up_idx' => array( 'tiki_copyrights', '`user_id`,`page_id`', array( 'UNIQUE' ) ),
		'tiki_footnotes_page_idx' => array( 'tiki_page_footnotes', '`page_id`', array() ),
		'tiki_footnotes_user_idx' => array( 'tiki_page_footnotes', '`user_id`', array() ),
		'tiki_footnotes_up_idx' => array( 'tiki_page_footnotes', '`user_id`,`page_id`', array( 'UNIQUE' ) ),
		'tiki_history_page_idx' => array( 'tiki_history', '`page_id`', array() ),
		'tiki_history_pv_idx' => array( 'tiki_history', '`page_id`,`version`', array( 'UNIQUE' ) ),
		'tiki_links_from_idx' => array( 'tiki_links', '`from_content_id`', array() ),
		'tiki_links_to_idx' => array( 'tiki_links', '`to_content_id`', array() ),
		'tiki_links_ft_idx' => array( 'tiki_links', '`from_content_id`,`to_content_id`', array( 'UNIQUE' ) ),
		'tiki_pages_content_idx' => array( 'tiki_pages', '`content_id`', array( 'UNIQUE' ) ),
	)),
)),


	)
),

	'BWR1' => array(
		'BWR2' => array(
// de-tikify tables
array( 'DATADICT' => array(
	array( 'RENAMETABLE' => array(
		'tiki_page_footnotes'     => 'wiki_footnotes',
		'tiki_pages'              => 'wiki_pages',
		'tiki_pages_page_id_seq'  => 'wiki_pages_page_id_seq',
		'tiki_received_pages'     => 'wiki_received_pages',
		'tiki_tags'               => 'wiki_tags',
		'tiki_extwiki'            => 'wiki_ext',
	)),
	array( 'RENAMECOLUMN' => array(
		'wiki_pages' => array(
			'`page_size`' => '`wiki_page_size` I4 DEFAULT 0',
			'`cache`' => '`page_cache` X',
			'`comment`' => '`edit_comment` C(200)',
		),
	)),
)),

array('QUERY' =>
	array( 'SQL92' => array(
		"INSERT INTO `".BIT_DB_PREFIX."users_permissions` (`perm_name`,`perm_desc`, `perm_level`, `package`) VALUES ('p_wiki_view_history', 'Can view page history', 'basic', 'wiki')",
		"INSERT INTO `".BIT_DB_PREFIX."users_group_permissions` (`group_id`, `perm_name`) VALUES (-1,'p_wiki_view_history')",
		"UPDATE `".BIT_DB_PREFIX."kernel_config` SET config_name='content_allow_html' WHERE config_name='allow_html'",
		"INSERT INTO `".BIT_DB_PREFIX."liberty_content_data` (`content_id`,`data`,`data_type`) (SELECT `content_id`, `description`, 'summary' FROM `".BIT_DB_PREFIX."wiki_pages` WHERE `description` IS NOT NULL)",
	),
)),

array( 'DATADICT' => array(
	array( 'DROPCOLUMN' => array(
		'wiki_pages' => array(
			'page_cache',
			'wiki_cache',
			'cache_timestamp',
			'votes',
			'points',
			'page_rank',
			'description'
		),
	)),
)),

		)
	),

);

if( isset( $upgrades[$gUpgradeFrom][$gUpgradeTo] ) ) {
	$gBitSystem->registerUpgrade( WIKI_PKG_NAME, $upgrades[$gUpgradeFrom][$gUpgradeTo] );
}


?>
