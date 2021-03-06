<?php
/**
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See below for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details
 *
 * @package wiki
 * @subpackage functions
 */

/**
 * required setup
 */
require_once( '../kernel/includes/setup_inc.php' );
include_once( UTIL_PKG_INCLUDE_PATH.'zip_lib.php' );
include_once( WIKI_PKG_INCLUDE_PATH.'export_lib.php' );
if (!$gBitUser->hasPermission( 'p_wiki_admin' ))
	die;
if (!isset($_REQUEST["page_id"])) {
	$exportName = 'export_'.date( 'Y-m-d_H:i' ).'.tar';
	$exportlib->MakeWikiZip( TEMP_PKG_PATH.$exportName );
	header ("location: ".TEMP_PKG_URL.$exportName );
} else {
	if (isset($_REQUEST["all"]))
		$all = 0;
	else
		$all = 1;
	$data = $exportlib->export_wiki_page($_REQUEST["page_id"], $all);
	$pageId = $_REQUEST["page_id"];
	header ("Content-type: application/unknown");
	header ("Content-Disposition: inline; filename=$pageId");
	echo $data;
}
?>
