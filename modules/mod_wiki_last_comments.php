<?php
/**
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See below for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details
 *
 * @package wiki
 * @subpackage modules
 */

/**
 * Show last comments on wiki pages
 */
global $gQueryUserId, $moduleParams;
/**
 * required setup
 */
if( $gBitUser->hasPermission( 'p_wiki_view_page' ) ) {
	require_once( LIBERTY_PKG_CLASS_PATH.'LibertyComment.php' );
	$cmt = new LibertyComment();
	$listHash = array( 'max_records' => $moduleParams['module_rows'], 'user_id' => $gQueryUserId, 'content_type_guid' => BITPAGE_CONTENT_TYPE_GUID );
	$lastComments = $cmt->getList( $listHash );
	$_template->tpl_vars['lastComments'] = new Smarty_variable( $lastComments);
	$_template->tpl_vars['moretooltips'] = new Smarty_variable( isset($module_params["moretooltips"]) ? $module_params["moretooltips"] : 'n');
}
?>
